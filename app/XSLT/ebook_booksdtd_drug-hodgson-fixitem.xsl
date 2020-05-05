<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:bk="http://www.elsevier.com/xml/bk/dtd"
				xmlns:html="http://www.w3.org/1999/xhtml"
				xmlns:aid="http://ns.adobe.com/AdobeInDesign/4.0/"
				xmlns:sb="http://www.elsevier.com/xml/common/struct-bib/dtd"
				xmlns:ce="http://www.elsevier.com/xml/common/dtd"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
				exclude-result-prefixes="bk html aid sb ce xlink mml">
				
	<!--
		Identifies and creates a highlighted HTML page containing the text and nodes following a bulleted list in which the first item had no child nodes but a subsequent one did. These were truncated at the first child node in the initial ingestion
		2017-10-13 JWS	original
	-->				
				
				
<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 media-type="text/html"/>
	
<xsl:strip-space elements="ce:section ce:section-title ce:para ce:list ce:list-item"/>
<xsl:preserve-space elements="br"/>



<xsl:template match="*"/>


<xsl:template match="/bk:root|/chapter">
<html>
	<head>
		<meta charset="utf-8" />
		<title>METIS: Kizior: unordered list items with missing text</title>
		<style>
			h1 {
				padding:0.5em;
				font-size:140%;
				color:#eeeeee;
				background-color:#e9711c;
			}
			dd + dt {
				margin-top:0.75em;
			}
			dd {
				border:dotted 1px transparent;
				padding:1px;
			}
			dt:hover + dd, dd:hover {
				border-color:#02d61e;
			}
			.found {
				display:inline-block;
			}
			.found, .sec_title {
				color:#aaaaaa;
			}
			.badnode {
				font-weight:bold;
				color:#ffffff;
				background-color:#ff0000;
			}
			.anchor {
				margin:0.5em;
				border:solid 2px #ff0000;
				border-radius:3px;
				padding:0.5em;
				font-family:Tahoma,Arial,sans-serif;
				font-size:60%;
				white-space:nowrap;
			}
		</style>
	</head>
	<body>
	<p>These were paragraphs in the source content and used bullet characters (• ) to replicate an inline unordered list. When transforming these for ingestion, whenever the first list item did not have a child node (eg, bold, inferior, page anchor), but a subsequent item <em>did</em>, all text beginning with that child node was omitted from the output. The two lists below capture where this happened, first wherever a <strong class="badnode">style element</strong> was the culprit and second when the <strong class="anchor">page anchor</strong> may have been the culprit. Often the page anchors appeared at the end of the list so no text went missing. Whenever both a <strong class="badnode">style element</strong> and <strong class="anchor">page anchor</strong> may have broken the output, that entry will appear in both lists.</p>

	<article>
		<h1>Nodes broken by styles (eg, bold, italic, inferior, superior)</h1>
		<dl>
			<xsl:apply-templates select="//ce:para[contains(., '• ')][*][(node()[1][local-name()=''] and count(tokenize(node()[1], '• ')) > 2 and count(*[not(local-name()='anchor')]) > 0) or (node()[1][local-name()='bold'] and node()[2][local-name()=''] and count(tokenize(node()[2], '• ')) > 2 and count(*[not(local-name()='anchor')]) > 1)]"/>
		</dl>
	</article>
	<article>
		<h1>Nodes broken by anchors (may duplicate some with styles)</h1>
		<dl>
			<xsl:apply-templates select="//ce:para[contains(., '• ')][*][(node()[1][local-name()=''] and count(tokenize(node()[1], '• ')) > 2 and count(*[local-name()='anchor']) > 0) or (node()[1][local-name()='bold'] and node()[2][local-name()=''] and count(tokenize(node()[2], '• ')) > 2 and count(*[local-name()='anchor']) > 0)]"/>
		</dl>
	</article>
</body>
</html>
</xsl:template>

<xsl:template match="ce:para">
	<xsl:apply-templates select="ancestor::ce:section[ce:section-title and parent::ce:section[not(ce:section-title)]][last()]/ce:section-title"><xsl:with-param name="position" select="position()"/></xsl:apply-templates>
		<dd><xsl:apply-templates mode="badnode"/></dd>

</xsl:template>

<xsl:template match="ce:section-title">
	<xsl:param name="position"/>
	<dt><strong><xsl:value-of select="$position"/></strong><xsl:text> </xsl:text><xsl:apply-templates mode="title"/></dt>
</xsl:template>

<xsl:template match="text()" mode="title">
	<xsl:copy select=".[normalize-space()]"/>
</xsl:template>

	<xsl:template match="@*|node()" mode="badnode">
		<xsl:copy copy-namespaces="no" exclude-result-prefixes="ce">
			<xsl:apply-templates select="@*|node()" mode="#current"/>
		</xsl:copy>
	</xsl:template>

<xsl:template match="ce:anchor" mode="badnode">
	<strong class="anchor">PAGE ANCHOR (<xsl:value-of select="@id"/>)</strong>
</xsl:template>

<xsl:template match="ce:bold" mode="badnode">
	<strong class="badnode"><xsl:apply-templates mode="#current"/></strong>
</xsl:template>

<xsl:template match="ce:bold[not(preceding-sibling::*) and not(preceding-sibling::text())]" mode="badnode">
	<strong class="sec_title"><xsl:apply-templates/></strong>
</xsl:template>

<xsl:template match="ce:inf" mode="badnode">
	<sub class="badnode"><xsl:apply-templates mode="#current"/></sub>
</xsl:template>

<xsl:template match="ce:italic" mode="badnode">
	<em class="badnode"><xsl:apply-templates mode="#current"/></em>
</xsl:template>


<xsl:template match="ce:sup" mode="badnode">
	<sup class="badnode"><xsl:apply-templates mode="#current"/></sup>
</xsl:template>

<xsl:template match="text()[parent::ce:para and (not(preceding-sibling::*) or preceding-sibling::*[1][local-name()='bold' and not(preceding-sibling::*) and not(preceding-sibling::text())])]" mode="badnode">
	<div class="found"><xsl:apply-templates select="."/></div>
</xsl:template>




</xsl:stylesheet>
