<?xml version="1.0" encoding="utf-8" ?>
<xsl:transform  version="1.0"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>
<xsl:output method="html"/>

<xsl:template match="/">
	<xsl:apply-templates select="node()"/>
</xsl:template>

<xsl:template match="atom:feed">
	<xsl:for-each select="atom:entry">
		<article class="post collapsible">
			<h1>
				<a><xsl:attribute name="href"><xsl:value-of select="atom:id" disable-output-escaping="no"/></xsl:attribute>
				<xsl:value-of select="atom:title" disable-output-escaping="yes"/></a>
			</h1>
			<div>
				<xsl:value-of select="atom:content" disable-output-escaping="yes"/>
			</div>
		</article>
	</xsl:for-each>
</xsl:template>

</xsl:transform>
