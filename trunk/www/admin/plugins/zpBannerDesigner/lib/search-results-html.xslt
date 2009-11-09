<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
  <xsl:output omit-xml-declaration = "yes" />

  <xsl:include href="common-templates.xslt" />

  <xsl:template match="rss/channel">
    <xsl:call-template name="search-results" />
  </xsl:template>
</xsl:stylesheet>
