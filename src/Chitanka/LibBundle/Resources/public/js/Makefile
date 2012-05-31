COMPRESSOR=${HOME}/bin/yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar
ARGS=--nomunge
MINSUFFIX=.min
SRCS=main.js jquery-myplugins.js book.js history.js pic.js text.js jquery-hoverIntent.js jquery-cluetip.js \
	jquery-tablesorter.js jquery-tooltip.js jquery-hotkeys.js jquery-galleriffic.js \
	jquery-cookie.js diff_match_patch.js
MINS=${SRCS:.js=${MINSUFFIX}.js}

all: minify

minify: ${MINS}

%${MINSUFFIX}.js: %.js
	java -jar ${COMPRESSOR} ${ARGS} $< -o ${<:.js=$(MINSUFFIX).js}
