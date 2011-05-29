<?xml version="1.0" encoding="utf-8" ?>
<xsl:transform  version="1.0"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
>
<xsl:output method="html"/>

<xsl:template match="/">
	<xsl:apply-templates select="node()"/>
</xsl:template>

<xsl:template match="atom:content">
	<xsl:choose>
		<xsl:when test="@type = 'xhtml'">
			<xsl:copy-of select="." disable-output-escaping="yes"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="." disable-output-escaping="yes"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template match="atom:source">
	<p><b><xsl:value-of select="atom:title" disable-output-escaping="yes"/>:</b></p>
</xsl:template>

<xsl:template match="atom:link">
	<xsl:value-of select="@href" disable-output-escaping="yes"/>
</xsl:template>

<xsl:template match="atom:feed">
	<xsl:for-each select="atom:entry">
		<article class="post collapsible">
			<xsl:apply-templates select="atom:source"/>
			<h1>
				<a>
					<xsl:attribute name="href">
						<xsl:choose>
							<xsl:when test="atom:link[@rel='alternate']">
								<xsl:apply-templates select="atom:link[@rel='alternate']"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:apply-templates select="atom:link"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:value-of select="atom:title" disable-output-escaping="yes"/>
				</a>
			</h1>
			<div>
				<xsl:apply-templates select="atom:content"/>
			</div>
		</article>
	</xsl:for-each>
</xsl:template>

</xsl:transform>
