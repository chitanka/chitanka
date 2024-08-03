<?php namespace App\Generator;

// from phpmyadmin

/**
 * Zip file creation class.
 * Makes zip files.
 *
 * Based on:
 *
 *  http://www.zend.com/codex.php?id=535&single=1
 *  By Eric Mueller <eric@themepark.com>
 *
 *  http://www.zend.com/codex.php?id=470&single=1
 *  by Denis125 <webmaster@atlant.ru>
 *
 *  a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
 *  date and time of the compressed file
 *
 * Official ZIP file format: http://www.pkware.com/appnote.txt
 *
 * Modified 2006, by Borislav Manolov
 */
class ZipFile {
	/**
	 * Array to store compressed data
	 * @var  array    $datasec
	 */
	private $datasec      = [];

	/**
	 * Central directory
	 * @var  array    $ctrl_dir
	 */
	private $ctrl_dir     = [];

	/**
	 * End of central directory record
	 * @var  string   $eof_ctrl_dir
	 */
	private $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

	/**
	 * Last offset position
	 * @var  integer  $old_offset
	 */
	private $old_offset   = 0;
	private $old_offset_ph = '__OLD_OFFSET__';

	/**
	 * Converts an Unix timestamp to a four byte DOS date and time format (date
	 * in high two bytes, time in low two bytes allowing magnitude comparison).
	 *
	 * @param  integer  the current Unix timestamp
	 * @return integer  the current date in a four byte DOS format
	 */
	private function unix2DosTime($unixtime = 0) {
		$timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

		if ($timearray['year'] < 1980) {
			$timearray['year']    = 1980;
			$timearray['mon']     = 1;
			$timearray['mday']    = 1;
			$timearray['hours']   = 0;
			$timearray['minutes'] = 0;
			$timearray['seconds'] = 0;
		}

		return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) |
			 ($timearray['mday'] << 16) | ($timearray['hours'] << 11) |
			 ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
	}

	/**
	 * Creates new file entry as array
	 *
	 * @param string $data file contents
	 * @param string $name name of the file in the archive (may contains the path)
	 * @param int $time the current timestamp
	 * @param bool $compress
	 */
	public function newFileEntry($data, $name, $time = 0, $compress = true) {
		$name     = str_replace('\\', '/', $name);

		$dtime    = dechex($this->unix2DosTime($time));
		$hexdtime = '\x' . $dtime[6] . $dtime[7]
			. '\x' . $dtime[4] . $dtime[5]
			. '\x' . $dtime[2] . $dtime[3]
			. '\x' . $dtime[0] . $dtime[1];
		eval('$hexdtime = "' . $hexdtime . '";');

		$compression_method = $compress ? "\x08\x00" : "\x00\x00";

		$fr = "\x50\x4b\x03\x04";
		$fr.= "\x14\x00";          // ver needed to extract
		$fr.= "\x00\x00";          // gen purpose bit flag
		$fr.= $compression_method; // compression method
		$fr.= $hexdtime;           // last mod time and date

		// "local file header" segment
		$unc_len = strlen($data);
		$crc     = crc32($data);
		if ($compress) {
			$zdata = gzcompress($data);
			$zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
		} else {
			$zdata = $data;
		}
		unset($data);
		$c_len   = strlen($zdata);
		$fr .= pack('V', $crc);         // crc32
		$fr .= pack('V', $c_len);       // compressed filesize
		$fr .= pack('V', $unc_len);     // uncompressed filesize
		$fr .= pack('v', strlen($name));// length of filename
		$fr .= pack('v', 0);            // extra field length
		$fr .= $name;

		// "file data" segment
		$fr .= $zdata;
		unset($zdata);

		// now add to central directory record
		$cdrec = "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00";                // version made by
		$cdrec .= "\x14\x00";                // version needed to extract
		$cdrec .= "\x00\x00";                // gen purpose bit flag
		$cdrec .= $compression_method;       // compression method
		$cdrec .= $hexdtime;                 // last mod time & date
		$cdrec .= pack('V', $crc);           // crc32
		$cdrec .= pack('V', $c_len);         // compressed filesize
		$cdrec .= pack('V', $unc_len);       // uncompressed filesize
		$cdrec .= pack('v', strlen($name) ); // length of filename
		$cdrec .= pack('v', 0 );             // extra field length
		$cdrec .= pack('v', 0 );             // file comment length
		$cdrec .= pack('v', 0 );             // disk number start
		$cdrec .= pack('v', 0 );             // internal file attributes
		$cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

		// placeholder for relative offset of local header
		$cdrec .= $this->old_offset_ph;
		$cdrec .= $name;

		return compact('name', 'fr', 'cdrec');
	} // end of the 'newFileEntry()' method

	/**
	 * @see newFileEntry
	 * @param string $data
	 * @param string $name
	 * @param int $time
	 * @param bool $compress
	 */
	public function addNewFileEntry($data, $name, $time = 0, $compress = true) {
		$this->addFileEntry($this->newFileEntry($data, $name, $time, $compress));
	}

	/**
	 * Adds previously created file entry to archive
	 * @param array $fileEntry Associative array
	 */
	public function addFileEntry($fileEntry) {
		$this->datasec[] = $fileEntry['fr'];
		$fileEntry['cdrec'] = str_replace($this->old_offset_ph,
			pack('V', $this->old_offset), $fileEntry['cdrec']);
		$this->old_offset += strlen($fileEntry['fr']);
		$this->ctrl_dir[] = $fileEntry['cdrec'];
	}

	/**
	 * Dumps out file
	 *
	 * @return  string  the zipped file
	 */
	public function file() {
		$data    = implode('', $this -> datasec);
		$ctrldir = implode('', $this -> ctrl_dir);
		$size = sizeof($this -> ctrl_dir);
		unset($this->datasec, $this->ctrl_dir);
		return $data . $ctrldir . $this -> eof_ctrl_dir .
			pack('v', $size) .  // total # of entries "on this disk"
			pack('v', $size) .  // total # of entries overall
			pack('V', strlen($ctrldir)) . // size of central dir
			pack('V', strlen($data)) . // offset to start of central dir
			"\x00\x00"; // .zip file comment length
	}

}
