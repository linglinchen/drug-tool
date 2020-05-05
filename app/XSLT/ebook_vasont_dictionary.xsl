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
	Vasont Dictionary to Dictionary DTD XML; derived from ebook.xsl which transformed Aptara's EPUB-based XHTML; bk namespace is default, sb is not defined in XML and therefore in default namespace, aid is only used in proofs; built for Skidmore-Roth NDR 2017, YMMV
	2016-12-09 JWS	original
	2017-02-17 JWS	changes made to produce final output for updated DTD; punc, syl are optional; $book_type; proper nesting mechanism for mword/sword/ssword; proper counting for sortorder
	2017-05-24 JWS	label and credit extracted from fig.caption; support defgroup/para and distinguish it from defgroup/def; plural and etymology added to form and pronun refactored with these; removed containing para from around component; moved visual styles upward in template to correct precedence
	2017-09-21 JWS	ref.mw wasn't applied to all modes and therefore dropped all xrefs in def (retained in para)


	0. be sure your XSL transformation tool supports XSLT 2
	1. copy the DOCTYPE over the DOCTYPE in the source XML. This removes the unfollowable reference to the old DTD and adds support for known entities
	2. transform the source XML against this XSLT
		a. add any missing entities to both the source XML and this XSLT
		b. use $devmode='Y' to check for missed elements, retain original IDs or other things you'd want to do during testing
		c. verify that other params are set as you'd like them, eg, $defmarker, $imagenaming, $syl
	3. save transformed XML in a batch directory following this naming convention letter_a_4e.xml (lowercase and edition included)
		a. the main Dictionary DTD needs to be followable to ensure the output is valid. Default location is Y:\WWW1\METIS\Dictionary_4_3.dtd and can be changed in this XSLT
	4. ZIP batch directory and upload to converted_XML bucket in project's bucket on S3

	-->
	<!-- TODO: normalize-space() everywhere -->


<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD dictionary DTD version 1.0//EN//XML" doctype-system="Y:\WWW1\METIS\Dictionary_4_4.dtd"
 media-type="text/html"/>

<xsl:strip-space elements="ce:section ce:section-title ce:para ce:list ce:list-item"/>
<xsl:preserve-space elements="br"/>

  	<!-- here just in case they need to be; delete if not used -->
  <xsl:param name="thispage" select="'NONE'"/>	<!-- optional; indicates the requesting page path; requires usage_type to be able to look for a specific item -->
  <xsl:param name="asset_location" select="'book/'"/>
  <xsl:param name="searchedpage" select="'NONE'"/>	<!-- optional; path of page being searched -->
  <xsl:param name="browser"/>
  <xsl:param name="sectionID" select="'NONE'"/>	<!-- optional; requests a specific ID on the XML document to look for -->
  <xsl:param name="book_type" select="'shared'"/>	<!-- used to indicate which product an entry/definition belongs to if more than one is contained in XML, but should always be 'shared' now that DTDs are merged; historically was mosby|dorland|shared) -->
  <xsl:param name="devmode" select="'N'"/>	<!-- TODO: reset to "N" --><!-- "brokenimage" will hide FigureCaption in production when images are missing; "Y" replaces broken images with error message -->
  <xsl:param name="defmarker" select="'ordinal'"/>	<!-- "ordinal" will only identify def elements starting with a number as defs and the remainder will be para (Vet Dictionary) -->
  <xsl:param name="imagenaming" select="'simple'"/>	<!-- "simple" will transform images from on133-040-9780323172929.jpg to 133040.jpg -->
  <xsl:param name="punc" select="'NONE'"/>	<!-- default, NONE, supresses automatic output of punc node, otherwise place content of this node here -->
  <xsl:param name="syl" select="'NONE'"/>	<!-- default, NONE, supresses automatic retention of syl node's ids -->
  
<!-- TODO: delete when XSL is complete -->  
	<xsl:variable name="search" select="'NOSEARCH'"/>
<xsl:variable name="search_term" select="'NOSEARCH'"/>
<xsl:variable name="search_term_UPPER" select="'NOSEARCH'"/>
  <xsl:param name="searchedpage_type" select="'searchedpage'"/>
  <xsl:param name="inlineasthumb" select="'NONE'"/>	<!-- IDs of inline-figures that will be displayed as thumbnails surrounded and separated with |: |fx1|fx2| -->
	<xsl:variable name="newline">
		<xsl:text>
</xsl:text>
	</xsl:variable>
<!-- delete above when XSL is complete -->
  

  <xsl:variable name="document_root">
    <xsl:choose>
      <xsl:when test="/bk:chapter">
        <xsl:text>chapter</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:fb-non-chapter">
        <xsl:text>fb-non-chapter</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:introduction">
        <xsl:text>introduction</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>/</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <xsl:variable name="document_location">
    <xsl:choose>
      <xsl:when test="/bk:fb-non-chapter and /bk:fb-non-chapter/@docsubtype='htu'">
        <xsl:text>front</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:introduction or /bk:chapter"><!--@docsubtype="chp|itr"-->
        <xsl:text>body</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:fb-non-chapter and /bk:fb-non-chapter/@docsubtype='app'">
        <xsl:text>rear</xsl:text>
      </xsl:when>
      <xsl:otherwise></xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <xsl:variable name="document_id" select="/chapter/@id"/> <!-- c00001 -->
  	<xsl:variable name="document_type" select="substring($document_id,1,1)"/>
  	<xsl:variable name="document_subtype" select="/chapter/@docsubtype"/> <!-- chp -->
  	<xsl:variable name="document_isbn" select="translate(/*/info/ce:isbn, '-', '')"/><!-- works for both /chapter and /fb-non-chapter; translate(/bk:chapter/bk:info/ce:isbn, '-', '') -->
  	<xsl:variable name="document_pii" select="translate(/chapter/info/ce:pii, '-.', '')"/><!-- translate(/bk:chapter/bk:info/ce:pii -->
  	<xsl:variable name="document_copyright" select="/chapter/info/ce:copyright"/><!-- /bk:chapter/bk:info/ce:copyright -->
  	<xsl:variable name="document_copyright_year" select="/chapter/info/ce:copyright/@year"/><!-- /bk:chapter/bk:info/ce:copyright/@year -->
  	<!--<xsl:variable name="document_section" select="substring($document_id,15,1)"/>-->
  	<xsl:variable name="document_chapter" select="substring($document_id,2)"/>
  		<xsl:variable name="document_chapter_zeroed"><xsl:number value="$document_chapter" format="001" /></xsl:variable>
  	<!--<xsl:variable name="document_checkdigit" select="substring($document_id,20,1)"/>-->



<xsl:template match="root">
	<dictionary>
		<body>
			<xsl:apply-templates/>
		</body>
	</dictionary>
</xsl:template>


<xsl:template match="extract.alpha">
	<alpha>
		<!-- normalize initial letter -->
		<xsl:attribute name="letter"><xsl:value-of select="translate(upper-case(substring(alpha[1]/entry[1]/mword[1]/word, 1, 1)), 'αβγδζΑΒΓΔΖ', 'abgdzABGDZ')"/></xsl:attribute>
		<xsl:apply-templates/>
	</alpha>
</xsl:template>

<xsl:template match="alpha">
	<xsl:apply-templates>
		<xsl:with-param name="sortorder"><xsl:number/></xsl:with-param>
	</xsl:apply-templates>
</xsl:template>


<!-- entry is used like mword here; sword and ssword are treated as entry for now, nested within the main word -->
<xsl:template match="mword|sword|ssword">
	<xsl:param name="sortorder" select="position()"/>
	<entry sortorder="{$sortorder}" type="{$book_type}">
		<xsl:if test="$syl != 'NONE' or $devmode = 'Y'">
			<xsl:attribute name="syl_id_legacy" select="mword/syl/@id|syl/@id"/>
			<xsl:attribute name="syl_legacy" select="mword/syl/text()|syl/text()"/>
		</xsl:if>
		<xsl:apply-templates/>
		
		<xsl:apply-templates select=".[pron or def[starts-with(normalize-space(.), 'pl. ')] or def[matches(., '\[(Af|Afr|Ar|F|Fr|Ger|Gr|It|L|Port|Scand|Span)\.\]')]]" mode="form"/>
		
		<!-- definitions are contained within defgroup -->
		<defgroup type="{$book_type}">
			<xsl:if test="$punc != 'NONE'"><punc>,</punc></xsl:if>
			<xsl:apply-templates select="def" mode="def"/>
		
		</defgroup>
		
		<!-- mword, sword and ssword are siblings in source, but descendants in Dictionary DTD; this restructures the source into the desired output -->
		<xsl:if test="local-name() = 'mword'">
			<xsl:apply-templates select="parent::entry/sword|parent::entry/ssword">
			</xsl:apply-templates>
		</xsl:if>
	</entry>
</xsl:template>



<!-- BEGIN visual style templates -->
<!-- TODO: add mode="#current" to more than just it (used by plural) -->
<!-- regular set -->
<xsl:template match="b" mode="#all">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="bolditalic" mode="#all">
  <emphasis style="bold"><emphasis style="italic"><xsl:apply-templates/></emphasis></emphasis>
</xsl:template>
<xsl:template match="inf" mode="#all">
  <emphasis style="inf"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="it" mode="#all">
  <emphasis style="italic"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<xsl:template match="sans-serif" mode="#all">
  <emphasis style="sans-serif"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="sc" mode="#all">
  <emphasis style="smallcaps"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="sup" mode="#all">
  <emphasis style="sup"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="underline" mode="#all">
  <emphasis style="underline"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- END visual style templates -->



<!-- this contains main, subword and subsubwords, but will capture only mword so sword and ssword can be nested within -->
<xsl:template match="entry">
	<xsl:param name="sortorder"/>
	<xsl:apply-templates select="mword">
		<xsl:with-param name="sortorder" select="$sortorder"/>
	</xsl:apply-templates>
</xsl:template>


<!-- the term -->
<xsl:template match="word">
	<headw>
		<xsl:attribute name="id" select="concat('hw', '_REPLACE_ME__')"/>
		<xsl:apply-templates/>
	</headw>

</xsl:template>

<!-- subject classification of term -->
<xsl:template match="class">
	<category>
		<xsl:attribute name="cat_id" select="concat('cat', '_REPLACE_ME__')"/>
		<xsl:apply-templates/>
	</category>
</xsl:template>

<!-- suppress this; included in form -->
<xsl:template match="pron"/>

<!-- pronunciation -->
<xsl:template match="pron" mode="pronunciation">
		<pronun>
			<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
			<xsl:apply-templates/>
		</pronun>

</xsl:template>

<!-- cross-reference to another word -->
<!-- TODO: rename this element to match drug DTD -->
<xsl:template match="ref.mw" mode="#all">
	<xref>
		<xsl:attribute name="refid" select="concat('tra_', '_REFID__')"/>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@rid"/></xsl:if>
		<xsl:apply-templates mode="#current"/>
	</xref>

</xsl:template>

<!-- syllable? Ignored here because values are added to entry if included at all -->
<xsl:template match="syl"/>

<!-- definition ignored because it's selected using a mode -->
<xsl:template match="def"/>

<!-- used for figures in entry or inline -->
<xsl:template match="figure" mode="#all">
	<xsl:call-template name="figure"/>

</xsl:template>

<!-- used for figures that are block elements inside def because these require containing para -->
<!-- TODO: 2017-05-24 removed para because these output inside def or para already, but it might need to be restored -->
<xsl:template match="figure[parent::def and not(image[@type='FLOAT'])]" mode="#all">
		<xsl:call-template name="figure"/>

</xsl:template>

<!-- globally-used figure builder -->
<xsl:template name="figure">
	<component type="figure">
		<xsl:attribute name="id" select="concat('f', '_REPLACE_ME__')"/>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:apply-templates select="fig.caption/b" mode="figlabel"/>
		<!-- this guesses at the filename -->
		<xsl:if test="not(image)"><xsl:call-template name="figure_file"><xsl:with-param name="filename" select="concat(@id, '.jpg')"/></xsl:call-template></xsl:if>
		<xsl:apply-templates/>
	</component>
</xsl:template>

<xsl:template match="fig.caption">
	<caption>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:apply-templates mode="figcaption"/>
	</caption>
	<xsl:apply-templates select="text()" mode="figcredit"/>
</xsl:template>

<!-- caption -->
<xsl:template match="text()" mode="figcaption">
	<xsl:value-of select="."/>
</xsl:template>

<!-- remove leading space from start of caption --> 
<xsl:template match="text()[position()=1 and starts-with(., ' ')]" mode="figcaption">
	<xsl:value-of select="substring-after(., ' ')"/>
</xsl:template>

<!-- remove trailing space from end of caption -->
<xsl:template match="text()[position()=last() and ends-with(., ' ')]" mode="figcaption">
	<xsl:sequence select="replace(., '\s+$', '', 'm')"/>
</xsl:template>

<!-- ignore credit line, it's handled in figcredit -->
<xsl:template match="text()[contains(., '. From ')]" mode="figcaption">
	<xsl:value-of select="substring-before(normalize-space(.), '. From ')"/><xsl:text>.</xsl:text>
</xsl:template>

<!-- suppress, this will be separated from caption using figlabel -->
<xsl:template match="b[contains(., ':')]" mode="figcaption"/>
<!-- suppress non-matching text nodes -->
<xsl:template match="b" mode="figlabel"/>
<!-- the label is marked up in a b element and ends in a colon and needs to be placed outside caption -->
<xsl:template match="b[contains(., ':')]" mode="figlabel">
	<label><xsl:apply-templates/></label>
</xsl:template>

<!-- TODO: unwitnessed -->
<xsl:template match="fig.title">
	<fig-title>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:apply-templates/>
	</fig-title>
</xsl:template>
<!-- TODO: unwitnessed -->
<xsl:template match="fig.credit">
	<credit>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:apply-templates/>
	</credit>
</xsl:template>
<!-- suppress non-matching text nodes -->
<xsl:template match="text()" mode="figcredit"/>
<!-- contains text nodes following ". From" -->
<xsl:template match="text()[contains(., '. From ')]" mode="figcredit">
	<credit>
		<xsl:text>From </xsl:text><xsl:value-of select="substring-after(normalize-space(.), '. From ')"/>
	</credit>
</xsl:template>
<!-- see also figure_file -->
<xsl:template match="image">
	<xsl:call-template name="figure_file"><xsl:with-param name="filename" select="@file"/></xsl:call-template>
</xsl:template>
<!-- builds required file node when the @src isn't contained in source by using the @id -->
<xsl:template name="figure_file">
	<xsl:param name="filename"/>
	<file>
		<xsl:attribute name="src" select="concat('_REPLACE_LOCATION__', $filename)"/>
	</file>
</xsl:template>

<!-- form includes etymology, plural and pronunciation -->
<xsl:template match="mword|sword|ssword" mode="form">
	<form id="{concat('fm', '_REPLACE_ME__')}">
		<xsl:apply-templates select="pron" mode="pronunciation"/>
		<xsl:apply-templates select="def[1]/it[1]" mode="plural"/>
		<xsl:apply-templates select="def[1]" mode="etymology"/>
	</form>
</xsl:template>

<xsl:template match="it" mode="plural"/>

<xsl:template match="it[preceding-sibling::text()[contains(., 'pl. ')]]" mode="plural">
	<plural><xsl:apply-templates mode="#current"/></plural>
</xsl:template>

<!-- remove trailing ; -->
<xsl:template match="text()[parent::it and ends-with(., ';')]" mode="plural">
	<xsl:value-of select="substring-before(., ';')"/>
</xsl:template>

<!-- suppress non-matching text inside etymology -->
<xsl:template match="text()" mode="etymology"/>

<xsl:template match="def[matches(., '\[.*(Af|Afr|Ar|F|Fr|Ger|Gr|It|L|Port|Scand|Span)\..*\]')]" mode="etymology">
	<etymology><xsl:apply-templates select="text()" mode="#current"/></etymology>
</xsl:template>

<!-- suppress non-matching text strings -->
<xsl:template match="*|text()" mode="etymology"/>

<!-- normalizes abbreviation -->
<xsl:template match="text()[contains(., 'Afr.')]" mode="etymology">
	<lang group="afrikaans">Af.</lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Af.')]" mode="etymology">
	<lang group="afrikaans"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Ar.')]" mode="etymology">
	<lang group="arabic"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<!-- normalizes abbreviation -->
<xsl:template match="text()[contains(., 'F.')]" mode="etymology">
	<lang group="french">Fr.</lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Fr.')]" mode="etymology">
	<lang group="french"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Ger.')]" mode="etymology">
	<lang group="german"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Gr.')]" mode="etymology">
	<lang group="greek"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'It.')]" mode="etymology">
	<lang group="italian"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'L.')]" mode="etymology">
	<lang group="latin"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Port.')]" mode="etymology">
	<lang group="portugese"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Scand.')]" mode="etymology">
	<lang group="scandinavian"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., 'Span.')]" mode="etymology">
	<lang group="spanish"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>

<xsl:template match="text()[contains(., '., ')]" mode="etymology">
	<lang group="multiple"><xsl:value-of select="substring-after(substring-before(., ']'), '[')"/></lang>
</xsl:template>





<!-- Vasont used def in several different contexts -->
<!-- para are an encyclopedia entry and never to be numbered -->
<xsl:template match="def[$defmarker='ordinal']" mode="def">
	<para>
		<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<!-- <defnum style="bold">1. </defnum> -->
		<xsl:apply-templates/>
	</para>

</xsl:template>

<!-- definitions with numbers or no siblings are marked as def -->
<xsl:template match="def[$defmarker='ordinal' and (matches(normalize-space(.), '^\d\. ') or count(preceding-sibling::def|following-sibling::def) = 0)]" mode="def">
	<def>
		<xsl:attribute name="id" select="concat('d', '_REPLACE_ME__')"/>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:attribute name="n">
			<xsl:choose>
				<xsl:when test="matches(normalize-space(.), '^\d\. ')"><xsl:value-of select="substring-before(normalize-space(.), '. ')"/></xsl:when>
				<xsl:otherwise>1</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<!-- <defnum style="bold">1. </defnum> -->
		<xsl:apply-templates mode="#current"/>
	</def>

</xsl:template>

<!-- TODO: build a simple def rule for $defmarker=non-ordinal -->

<xsl:template match="text()" mode="def">
	<xsl:value-of select="."/>
</xsl:template>

<!-- suppress plural marker -->
<xsl:template match="text()[starts-with(., 'pl. ')]" mode="def"/>

<!-- suppress plural text -->
<xsl:template match="it[preceding-sibling::text()[starts-with(., 'pl. ')]]" mode="def"/>

<!-- removes ordinals, they are handled with @n -->
<xsl:template match="text()[$defmarker='ordinal' and matches(normalize-space(.), '^\d\. ')]" mode="def">
	<xsl:value-of select="substring-after(., '. ')"/>
</xsl:template>








<!-- copies every element along with its attributes, applying above templates as required; devmode highlights missed elements -->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:if test="$devmode = 'Y'">
			<xsl:attribute name="devmode"><xsl:value-of select="name()"/></xsl:attribute>
			<xsl:attribute name="templatemode"></xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="node()"/>
	</xsl:copy>
</xsl:template>



</xsl:stylesheet>