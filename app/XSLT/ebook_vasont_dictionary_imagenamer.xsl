<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE DICTIONARY [
	<!ENTITY dbobr  "ŏŏ" ><!-- double o with breves -->
	<!ENTITY ldquo  "&quot;" ><!-- left double quote -->
	<!ENTITY rdquo  "&quot;" ><!-- right double quote -->
	<!ENTITY rsquo  "&apos;" ><!-- right single quote -->
	<!ENTITY ndash  "–" ><!-- N-dash -->
]>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:bk="http://www.elsevier.com/xml/bk/dtd"
				xmlns:html="http://www.w3.org/1999/xhtml"
				xmlns:aid="http://ns.adobe.com/AdobeInDesign/4.0/"
				xmlns:sb="http://www.elsevier.com/xml/common/struct-bib/dtd"
				xmlns:ce="http://www.elsevier.com/xml/common/dtd"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
				exclude-result-prefixes="bk html aid sb ce xlink mml"><!-- remove mml and add default namespace (bk) when output includes MathML -->

	<!--
		Finds figures in Veterinary Dictionary XML processed from Vasont into Dictionary.DTD and turns them into something that can be found and replaced with something that *might* match the standard naming convention used for these files on S3
		2017-10-19 JWS	original

	-->


<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 media-type="text/html"/>


	<xsl:variable name="newline">
		<xsl:text>
</xsl:text>
	</xsl:variable>


<xsl:template match="*"/>


<xsl:template match="/dictionary">
	<xsl:text disable-output-escaping="yes">&lt;?php</xsl:text><xsl:value-of select="$newline"/>
	
	<xsl:text>/* these TIFs are likely character replacements and should not be replaced in source this way, but rather with MathML, etc
$searchreplace = array(</xsl:text><xsl:value-of select="$newline"/>

<xsl:apply-templates select="//alpha">
	<xsl:with-param name="filetype" select="'.tif'"/>
</xsl:apply-templates>

<xsl:text>
*/</xsl:text><xsl:value-of select="$newline"/><xsl:value-of select="$newline"/>
	
	<xsl:text>/* these should be replaced after checking that the filenames match up in random locations and the number of images found matches the number of images on S3
	- images that do not have a number in source are recorded here in such a way they will break the PHP if not commented out
	- images whose figure numbers were found in the wrong location are also recorded so they will break PHP if error message is not removed after verification of numbering
*/
$searchreplace = array(</xsl:text><xsl:value-of select="$newline"/>
	<xsl:apply-templates select="//alpha">
	<xsl:with-param name="filetype" select="'.jpg'"/>
</xsl:apply-templates>
	<xsl:text>);</xsl:text><xsl:value-of select="$newline"/>
	<xsl:text disable-output-escaping="yes">?&gt;</xsl:text>
</xsl:template>

<xsl:template match="alpha">
	<xsl:param name="filetype"/>
	<xsl:apply-templates select="descendant::component[@type='figure']/file[contains(@src, $filetype)]">
		<xsl:with-param name="filetype" select="$filetype"/>
	</xsl:apply-templates>
</xsl:template>

<xsl:template match="file">
	<xsl:param name="filetype"/>
	<xsl:if test="substring-before(substring-after(@src, '_REPLACE_LOCATION__'), $filetype) = ''"><xsl:text>ERROR_no_source_name_found</xsl:text></xsl:if>

	<xsl:variable name="fignum">
		<xsl:choose>
			<xsl:when test="substring-before(substring-after(parent::component/label, '-'), ':') = '' and not(parent::component/caption/emphasis[@style='bold'])"><!-- NULL --></xsl:when>
			<xsl:when test="parent::component/caption/emphasis[@style='bold']"><xsl:value-of select="substring-after(parent::component/caption/emphasis[@style='bold'], '-')"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="substring-before(substring-after(parent::component/label, '-'), ':')"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<!-- ERROR messages for figure number calculations -->
	<xsl:choose>
		<!-- this is a minor error -->
		<xsl:when test="not($fignum = '') and parent::component/caption/emphasis[@style='bold']"><xsl:text>ERROR_figure_number_in_caption</xsl:text></xsl:when>
		<xsl:when test="$fignum = ''">ERROR_no_figure_number_found</xsl:when>
	</xsl:choose>
	<xsl:text>	'/src="</xsl:text><xsl:value-of select="concat('_REPLACE_LOCATION__', substring-before(substring-after(@src, '_REPLACE_LOCATION__'), $filetype), $filetype)"/><xsl:text disable-output-escaping="yes">"/' =&gt; 'src="</xsl:text><xsl:value-of select="concat('f', format-number(count(preceding::alpha) + 1, '00') , '-', $fignum, '-9780702032318')"/><xsl:text>"',</xsl:text>
	<xsl:text> //</xsl:text><xsl:value-of select="ancestor::entry[parent::alpha]/headw//text()[normalize-space()]"/>
	<xsl:if test="parent::component/caption"><xsl:text>: </xsl:text><xsl:value-of select="parent::component/caption//text()[normalize-space()]"/></xsl:if>
	<xsl:value-of select="$newline"/>
</xsl:template>

</xsl:stylesheet>
