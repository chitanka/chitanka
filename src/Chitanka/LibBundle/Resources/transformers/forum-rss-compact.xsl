<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="html"/>
<xsl:template match="/">
	<script type="text/javascript" src="/phpBB2/resizeimg.js"></script>
	<xsl:apply-templates/>
</xsl:template>
<xsl:template match="/rss/channel">
<dl class="post">
	<xsl:for-each select="item">
	<dt>
		<a><xsl:attribute name="href"><xsl:value-of select="link" disable-output-escaping="no"/></xsl:attribute>
		<xsl:value-of select="title" disable-output-escaping="yes"/></a>
	</dt>
	<dd>
		<xsl:value-of select="description" disable-output-escaping="yes"/>
	</dd>
	</xsl:for-each>
</dl>
</xsl:template>
</xsl:stylesheet>
