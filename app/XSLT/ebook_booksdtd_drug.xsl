<?xml version="1.0" encoding="UTF-8"?>
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
		Books.dtd to Drugs DTD XML; derived from ebook.xsl which transformed Aptara's EPUB-based XHTML; bk namespace is default, sb is not defined in XML and therefore in default namespace, aid is only used in proofs; built for Skidmore-Roth NDR 2017, YMMV
		2016-06-13 JWS	original
		2016-06-28 JWS	some minor corrections to validate; eliminated wraplistitem; added para container for list-items missing list; monograph/bbw placed inside para; monograph/confusion is now section[@type=confusion]/para/confusion
		2016-07-01 JWS	XSL2; handling MathML; FIX_STRUCTURE when country located outside drug names; para containing both bbw and text; country splitter robustified; schedule
		2016-07-15 JWS	intra-ref:#all; include top-level section[@type=none] eg, "Treatment of overdose"; icon03-9780323448260 is also Canada; icon03-9780323448260 is also Nurse Alert; Func/Chem class[1]
		2016-07-22 JWS	support for appendices (fb-non-chapter); combined @ru/@ha; Canadian high-alert; fixed preg=UK; cross-ref distinguished from tradename; multi-line confusion paragraph handled properly and drugs separated
		2016-08-04 JWS	IDs are prefix + _REPLACE_ME__; tradename/@cpid=_CPID__; drug/@refid=_REFID__; corrected <route> to only be present in section=doses; corrected errors
		2017-01-30 JWS	pharmacodynamics added
		2017-05-30 JWS	modifications to improve support for other drug titles (eg, Kizior): monograph_attr; tables
		2017-08-15 JWS	extracted tables from para; added bk namespace to table descendant template rules; added root for concatenated chapters; sec_title text corrections; cell/@[colspan|rowspan|namest|nameend]; ftnote; legend; cross-ref; $confusion_prefix; $confusion_style; $confusion_location; pharma/clinical; fixed-combination; mediocre Title Casing with corrections; Kizior Nurse Alert; updated CANadian test; name=drugname; see type prefixing; DTD 3.6
		2017-11-08 JWS	added rules for NDG; bk namespace nodes duplicated as default namespaces; get_tradenames now only looks at first ce:italic; Pregnancy category found in ce:para; bold@subsection is assumed lifethreat with exceptions, $titlecase_sections; ce:para[ce:list] modifications for nested lists; role=sharp creates <group>; text normalized so testing on pretty/un-pretty is more nearly identical; lifethreat prevented inside tables; therapeutic outcomes w/o action ancestor are now given one; IV capitalization now safe
		2018-04-28 JWS	info changed to named template; HLH; inter-ref support (see)
		2018-05-25 JWS	ftnote-dagger, 2dagger
	-->
	<!-- TODO: normalize-space() everywhere -->
	
	<!-- INSTRUCTIONS FOR USE:
	0. check "tuned" comments for rules that might have been added since the last transformation of this XML structure that might break output
	1. run on a sample chapter to see how closely tuned you can make this XSL
		a. consider making a new XSLT that includes this one so override rules can be separated
		b. look at xsl:param settings before running (eg, $preg_term, $titlecase_sections, $confusion_prefix, $see_italic)
		c. some transformation failures may arise from nodes that are unexpectedly merged, split into two or nested (eg, <ce:bold>Pregnancy category C</ce:bold>, <ce:bold>Pregnancy category <ce:bold>C</ce:bold></ce:bold>)
		d. avoid changing DTD when possible
	2. make one short, sample XML file and one single XML combining all source chapters and required appendices within <root> parent
		a. <root> will need to have namespaces and all entitities will need to be declared
		b. short, sample XML can be first chapter, but should have examples of complicating nodes from other chapters - this is much faster for testing transformations
		c. use combined XML to find new structures and verify transformation rules being test on short XML are likely to work
	3. identify and manipulate combined source XML to accomodate or correct:
		a. make list of mistakes in lifethreat nodes before considering output final, these may need to be fixed manually
			1. BBW lifethreat are missed
			2. lifethreat subheads: //ce:section-title[ancestor::ce:section/ce:section-title[contains(lower-case(.), 'dosage')] and not(contains(lower-case(.), 'hepatic')) and not(contains(lower-case(.), 'renal'))][following-sibling::ce:para[1][not(text())]/ce:bold] 
		b. <group> monographs will need to have last mini-monograph/mini-group merged with full body of monograph to be properly transformed
	4. tune transformation till output is valid, contains all content and is accurate; manual fixes may be required
		a. look for MathML nodes, this transformation will flatten them; consider manual transformation and test at http://www.wiris.com/editor/demo/en/mathml-latex
		b. look for "FIX_STRUCTURE" and fix as necessary; add to list for Editorial/BPPM review
		c. nesting of para, list, etc may not be fixable except by manual intervention; add to list for Editorial/BPPM review
	5. save output to S3 in title's directory as converted_XML/batch_YYYY-MM-DD.zip
		a. expect to have to create multiple batches after developer acceptance
		b. structural review should take place, further batches may be necessary
		c. Quick Fixes should be reserved for things that absolutely cannot be fixed in transformation as they require additional import effort
	-->


<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD drug_guide DTD version 3.2//EN//XML" doctype-system="Y:\WWW1\METIS\Drugs\3_7_drug.dtd"
 media-type="text/html"/>

<xsl:strip-space elements="ce:section ce:section-title ce:para ce:list ce:list-item"/>
<xsl:preserve-space elements="br"/>

  	<!-- here just in case they need to be; delete if not used -->
  <xsl:param name="thispage" select="'NONE'"/>	<!-- optional; indicates the requesting page path; requires usage_type to be able to look for a specific item -->
  <xsl:param name="asset_location" select="'book/'"/>
  <xsl:param name="searchedpage" select="'NONE'"/>	<!-- optional; path of page being searched -->
  <xsl:param name="browser"/>
  <xsl:param name="sectionID" select="'NONE'"/>	<!-- optional; requests a specific ID on the XML document to look for -->
  <xsl:param name="devmode" select="'N'"/>	<!-- "brokenimage" will hide FigureCaption in production when images are missing; "Y" replaces broken images with error message and outputs other things including possibly missed sections; "full" displays the original XML at the end of the monograph -->
  <xsl:param name="imagenaming" select="'simple'"/>	<!-- "simple" will transform images from on133-040-9780323172929.jpg to 133040.jpg -->
  <xsl:param name="preg_term" select="'Pregnancy ('"/>
    <xsl:variable name="preg_term_UPPER" select="translate($preg_term, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/>
  <xsl:param name="confusion_prefix" select="'Do not confuse: '"/>
  <xsl:param name="confusion_style" select="'nodes'"/> <!-- 'nodes' result in multiple confusion elements (Skidmore), 'sentence' in a single one (Kizior) -->
  <xsl:param name="confusion_location" select="'first_sec'"/> <!-- 'first_sec' (Skidmore) places immediately after info; 'info' (Kizior) places inside info -->
  <xsl:param name="source_structure" select="'sections'"/>	<!-- where is the monograph corpus? Skidmore/Kizior use sections, HLH uses a list -->
  <xsl:param name="titlecase_sections" select="true()"/>	<!-- automatically changes sec_title to Title Case (NDG=false) -->
   <xsl:param name="see_italic" select="'remove'"/>	<!-- see does not allow emphasis within: Kizior=remove to delete these; NDG=invert to preserve and turn them inside out -->
  
	<xsl:variable name="newline">
		<xsl:text>
</xsl:text>
	</xsl:variable>

  

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
  	<xsl:variable name="document_isbn" select="translate(//bk:*[1]/bk:info/ce:isbn, '-', '')"/><!-- works for /chapter, /fb-non-chapter, /index; translate(/bk:chapter/bk:info/ce:isbn, '-', '') -->
  	<xsl:variable name="document_pii" select="translate(/chapter/info/ce:pii, '-.', '')"/><!-- translate(/bk:chapter/bk:info/ce:pii -->
  	<xsl:variable name="document_copyright" select="/chapter/info/ce:copyright"/><!-- /bk:chapter/bk:info/ce:copyright -->
  	<xsl:variable name="document_copyright_year" select="/chapter/info/ce:copyright/@year"/><!-- /bk:chapter/bk:info/ce:copyright/@year -->
  	<!--<xsl:variable name="document_section" select="substring($document_id,15,1)"/>-->
  	<xsl:variable name="document_chapter" select="substring($document_id,2)"/>
  		<xsl:variable name="document_chapter_zeroed"><xsl:number value="$document_chapter" format="001" /></xsl:variable>
  	<!--<xsl:variable name="document_checkdigit" select="substring($document_id,20,1)"/>-->



<!-- root is manually added when having concatenated multiple chapters into a single document -->
<xsl:template match="/root|/bk:root|/chapter|/bk:chapter|/fb-non-chapter|/bk:fb-non-chapter|/bk:index">
	<drug_guide>
		<xsl:attribute name="isbn"><xsl:value-of select="$document_isbn"/></xsl:attribute>
		<xsl:apply-templates/>
	</drug_guide>
</xsl:template>

<!-- alternate for concatenated chapters so body isn't repeated -->
<xsl:template match="chapter[parent::root]|bk:chapter[parent::bk:root]">
	<xsl:apply-templates/>
</xsl:template>


<xsl:template match="ce:sections|ce:section[parent::fb-non-chapter]">
		<alpha>
			<xsl:attribute name="letter"><xsl:value-of select="translate(lower-case(preceding-sibling::ce:title), 'αβγδζΑΒΓΔΖ', 'abgdzABGDZ')"/></xsl:attribute>
			
			<!-- mode is used to skip through layers of contentless ce:section -->
			<xsl:apply-templates mode="alpha"/>
		</alpha>
</xsl:template>

<!-- hides chapter information at beginning -->
<xsl:template match="bk:info|info"/>

<!-- hides chapter title at beginning -->
<xsl:template match="html:h1[@class='title_document']|ce:section-title[parent::ce:section[@role='sharp' and (parent::bk:fb-non-chapter or parent::fb-non-chapter)]]|ce:title[parent::bk:chapter or parent::chapter]|ce:title[parent::bk:fb-non-chapter or parent::fb-non-chapter]|ce:title[parent::bk:index or parent::index]"/>

<!-- hides author information at beginning -->
<xsl:template match="ce:author-group[parent::bk:fb-non-chapter or parent::fb-non-chapter]"/>


<xsl:template match="ce:display[ce:table]" mode="#all">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<xsl:template match="ce:table" mode="#all">
	<table>
		<!-- t1, t2, etc -->
		<xsl:attribute name="id"><xsl:value-of select="concat('t', count(ancestor::ce:section[ce:section-title and parent::ce:section[ce:section and not(ce:section-title)]]//ce:table[@id=current()/@id]/preceding::ce:table[ancestor::ce:section[ce:section-title and parent::ce:section[ce:section and not(ce:section-title)]] = current()/ancestor::ce:section[ce:section-title and parent::ce:section[ce:section and not(ce:section-title)]]]) + 1)"/></xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</table>

</xsl:template>

<xsl:template match="bk:tgroup|tgroup" mode="#all">
	<tgroup cols="{@cols}">
		<xsl:attribute name="id"><xsl:value-of select="concat('tg', '_REPLACE_ME__')"/></xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</tgroup>
</xsl:template>

<xsl:template match="bk:colspec|colspec" mode="#all">
	<colspec colname="{@colname}" colnum="{@colnum}">
		<xsl:apply-templates mode="#current"/>
	</colspec>
</xsl:template>

<xsl:template match="ce:label[parent::ce:table]" mode="#all">
	<label>
		<xsl:apply-templates mode="#current"/>
	</label>
</xsl:template>

<xsl:template match="ce:caption[parent::ce:table]" mode="#all">
	<ttitle>
		<xsl:apply-templates mode="#current"/>
	</ttitle>
</xsl:template>

<xsl:template match="ce:simple-para[parent::ce:caption]" mode="#all">
		<xsl:apply-templates mode="#current"/>
</xsl:template>

<xsl:template match="ce:simple-para[parent::ce:legend]" mode="#all">
		<legend><xsl:apply-templates mode="#current"/></legend>
</xsl:template>

<xsl:template match="bk:thead|thead" mode="#all">
	<thead>
		<xsl:apply-templates mode="#current"/>
	</thead>
</xsl:template>

<xsl:template match="bk:tbody|tbody" mode="#all">
	<tbody>
		<xsl:apply-templates mode="#current"/>
	</tbody>
</xsl:template>

<xsl:template match="ce:table-footnote|bk:table-footnote|table-footnote|ce:legend" mode="#all">
	<tfoot>
		<row>
			<xsl:attribute name="id"><xsl:value-of select="concat('tr', '_REPLACE_ME__')"/></xsl:attribute>
			<cell>
				<xsl:apply-templates mode="#current"/>
			</cell>
		</row>
	</tfoot>
</xsl:template>

<xsl:template match="bk:row|row" mode="#all">
	<row>
		<xsl:attribute name="id"><xsl:value-of select="concat('tr', '_REPLACE_ME__')"/></xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</row>
</xsl:template>

<xsl:template match="bk:entry|entry" mode="#all">
	<cell>
		<xsl:apply-templates select="@*|node()" mode="#current"/>
		<xsl:if test="not(node())"><xsl:text> </xsl:text></xsl:if>
	</cell>
</xsl:template>

<xsl:template match="@*[parent::bk:entry or parent::entry]" mode="#all"/>

<xsl:template match="@colspan[parent::bk:entry or parent::entry]|@rowspan[parent::bk:entry or parent::entry]" mode="#all">
	<xsl:copy><xsl:apply-templates select="."/></xsl:copy>
</xsl:template>

<!-- assumes all columns are prefixed "col", but should be paramaterized if not -->
<xsl:template match="@namest[parent::bk:entry or parent::entry]" mode="#all">
	<xsl:attribute name="colspan"><xsl:value-of select="number(substring-after(parent::*/@nameend, 'col')) - number(substring-after(., 'col')) + 1"/></xsl:attribute>
</xsl:template>

<xsl:template match="@morerows[parent::bk:entry or parent::entry]" mode="#all">
	<xsl:attribute name="rowspan"><xsl:value-of select=". + 1"/></xsl:attribute>
</xsl:template>

<xsl:template match="ce:label[parent::ce:table-footnote]" mode="#all"/>

<xsl:template match="ce:note-para" mode="#all">
	<ftnote>
		<xsl:attribute name="id" select="parent::ce:table-footnote/@id"/>
		<xsl:attribute name="char">
			<!-- Defined in DTD: ast | dagger | 2ast | 2dagger | a | b | c | 1 | 2 | 3 -->
			<xsl:choose>
				<xsl:when test="preceding-sibling::ce:label = '*'">ast</xsl:when>
				<xsl:when test="preceding-sibling::ce:label = '†'">dagger</xsl:when>
				<xsl:when test="preceding-sibling::ce:label = '‡'">2dagger</xsl:when>
				<xsl:otherwise>ERROR</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</ftnote>
</xsl:template>

<xsl:template match="ce:br" mode="#all">
	<xsl:text> </xsl:text>
</xsl:template>




<!-- below are old Book DTD to HTML rules, they may not be needed -->



<!-- these are explicitly included in table's tfoot -->
<xsl:template match="ce:legend|ce:table-footnote|ce:source[parent::ce:table]"/>
<!-- END table elements -->


<!-- BEGIN formula elements -->
<!-- Firefox/Safari/Opera will only support Presentation MathML, not Content MathML: https://developer.mozilla.org/en-US/docs/Web/MathML/Authoring; IE and Chrome do not support at all -->
<xsl:template match="ce:display[ce:formula]">
    <xsl:apply-templates mode="math"/>
</xsl:template>
<xsl:template match="ce:display[ce:formula]" mode="subsection">
    <xsl:apply-templates mode="math"/>
</xsl:template>
<xsl:template match="ce:display[ce:formula]" mode="subsubsection">
    <xsl:apply-templates mode="math"/>
</xsl:template>

<xsl:template match="ce:formula">
    <xsl:apply-templates mode="math"/>
</xsl:template>
<xsl:template match="ce:formula" mode="math">
    <xsl:apply-templates mode="math"/>
</xsl:template>

<xsl:template match="mml:math" mode="math">
	<math><xsl:apply-templates mode="#current"/></math>
</xsl:template>

<!-- flatten these away, they were probably only used for layout and that needs to happen exclusively in InDesign -->
<xsl:template match="mml:mtable|mml:mtr|mml:mtd" mode="math">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<!-- try to put literal numbers into mn -->
<xsl:template match="mml:mtext[matches(.[normalize-space()], '^[0-9,.]+$')]" mode="math">
	<mn><xsl:apply-templates mode="#current"/></mn>
</xsl:template>

<!-- cross-product symbol is often mistakenly used instead of multiplication symbol -->
<xsl:template match="mml:mtext[.='⨯']" mode="math">
	<mo>×</mo>
</xsl:template>

<xsl:template match="mml:mo|mml:mtext[.='=' or .='[' or .=']' or .='(' or .=')' or .='×' or .='÷' or .='+' or .='-']" mode="math">
	<mo><xsl:apply-templates mode="#current"/></mo>
</xsl:template>

<!-- everything else -->
<xsl:template match="mml:mtext" mode="math">
	<mi><xsl:apply-templates mode="#current"/></mi>
</xsl:template>


<xsl:template match="*" mode="math">
  <xsl:element name="mml:{local-name()}">
    <xsl:apply-templates mode="math"/>
  </xsl:element>
</xsl:template>
<!-- END formula elements -->


<!-- BEGIN textbox elements -->
<!-- these are all handled by div.local-name() block elements template -->

<!-- END textbox elements -->


<!-- ignore all other nodes in this mode, it is used to find a ce:section with content -->
<xsl:template match="*" mode="alpha"/>

<!-- Book XML often has several contentless ce:section elements nested above the content -->
<xsl:template match="ce:section[ce:section and not(ce:section-title)]" mode="alpha">
	<xsl:apply-templates mode="alpha"/>
</xsl:template>

<!-- finally, this contains content -->
<xsl:template match="ce:section[ce:section-title]" mode="alpha">
	<monograph>
		<xsl:call-template name="monograph_attr"/>

		<xsl:choose>
			<!-- tuned to HLH, monograph -->
			<xsl:when test="ce:para[ce:display]">
				<xsl:call-template name="info"/>
			</xsl:when>
			
			<!-- tuned to HLH, "see elsewhere" monograph -->
			<xsl:when test="ce:para[count(descendant::ce:list-item) = 1]">
				<xsl:apply-templates select="descendant::ce:list-item/ce:para" mode="see"/>
			</xsl:when>

			<!-- typical monograph -->
			<xsl:when test="ce:para[not(descendant-or-self::ce:intra-ref) and not(descendant-or-self::ce:cross-ref)] or ce:section">
				<xsl:call-template name="info"/>
			</xsl:when>

			<!-- "See elsewhere" monograph -->
			<xsl:otherwise>
				<xsl:apply-templates select="descendant::ce:para" mode="see"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="$confusion_location='first_sec'">
			<xsl:apply-templates select="ce:para[contains(ce:bold[1], 'Do not confuse:') or starts-with(ce:bold[1], $confusion_prefix)]" mode="confusion"/>
		</xsl:if>
		<!-- recognized section heads/group's sub-monographs (@role=sharp); adding anything here in first two blocks requires adding to ce:section mode=section $type below; third block excludes sections that should be members elsewhere -->
		<xsl:choose>
			<!-- Skidmore/Kizior monograph corpus -->
			<xsl:when test="$source_structure='sections'">
			<xsl:apply-templates select="descendant::ce:section[
					((contains(ce:section-title, 'ACTION:') or contains(ce:section-title, 'ACTIONS:') or ce:section-title = 'ACTION'
					or contains(ce:section-title, 'USE:') or contains(ce:section-title, 'USES:')
					or contains(ce:section-title, 'CONTRAINDICATION:') or contains(ce:section-title, 'CONTRAINDICATIONS:') or ce:section-title[.='CONTRAINDICATIONS']
					or contains(ce:section-title, 'CAUTION:') or contains(ce:section-title, 'CAUTIONS:') or normalize-space(ce:section-title) = 'PRECAUTIONS'
					or contains(ce:section-title, 'DOSAGES AND ROUTES') or contains(ce:section-title, 'DOSAGES AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTES') or ce:section-title = 'AVAILABILITY (Rx)' or ce:section-title= 'AVAILABILITY (OTC)' or ce:section-title= 'AVAILABILITY'
					or contains(ce:section-title, 'ADVERSE EFFECT') or contains(ce:section-title, 'ADVERSE EFFECTS')  or contains(ce:section-title, 'ADVERSE REACTIONS')
					or contains(ce:section-title, 'SIDE EFFECT') or contains(ce:section-title, 'SIDE EFFECTS')
					or contains(ce:section-title, 'KINETIC') or contains(ce:section-title, 'KINETICS')
					or contains(ce:section-title, 'PHARMACOKINETIC') or contains(ce:section-title, 'PHARMACOKINETICS')
					or contains(ce:section-title, 'DYNAMIC') or contains(ce:section-title, 'DYNAMICS')
					or contains(ce:section-title, 'PHARMACODYNAMIC') or contains(ce:section-title, 'PHARMACODYNAMICS')
					or contains(ce:section-title, 'INTERACTION') or contains(ce:section-title, 'INTERACTIONS')
					or contains(ce:section-title, 'NURSING CONSIDERATION') or contains(ce:section-title, 'NURSING CONSIDERATIONS'))
					or
					(count(ancestor::ce:section[ce:section-title]) = 1 and
					not(contains(ce:section-title, 'ACTION:') or contains(ce:section-title, 'ACTIONS:') or ce:section-title = 'ACTION'
					or contains(ce:section-title, 'USE:') or contains(ce:section-title, 'USES:')
					or contains(ce:section-title, 'CONTRAINDICATION:') or contains(ce:section-title, 'CONTRAINDICATIONS:') or ce:section-title[.='CONTRAINDICATIONS']
					or contains(ce:section-title, 'CAUTION:') or contains(ce:section-title, 'CAUTIONS:') or normalize-space(ce:section-title) = 'PRECAUTIONS'
					or contains(ce:section-title, 'DOSAGES AND ROUTES') or contains(ce:section-title, 'DOSAGES AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTES') or ce:section-title = 'AVAILABILITY (Rx)' or ce:section-title= 'AVAILABILITY (OTC)' or ce:section-title= 'AVAILABILITY'
					or contains(ce:section-title, 'ADVERSE EFFECT') or contains(ce:section-title, 'ADVERSE EFFECTS') or contains(ce:section-title, 'ADVERSE REACTIONS')
					or contains(ce:section-title, 'SIDE EFFECT') or contains(ce:section-title, 'SIDE EFFECTS')
					or contains(ce:section-title, 'KINETIC') or contains(ce:section-title, 'KINETICS')
					or contains(ce:section-title, 'PHARMACOKINETIC') or contains(ce:section-title, 'PHARMACOKINETICS')
					or contains(ce:section-title, 'DYNAMIC') or contains(ce:section-title, 'DYNAMICS')
					or contains(ce:section-title, 'PHARMACODYNAMIC') or contains(ce:section-title, 'PHARMACODYNAMICS')
					or contains(ce:section-title, 'INTERACTION') or contains(ce:section-title, 'INTERACTIONS')
					or contains(ce:section-title, 'NURSING CONSIDERATION') or contains(ce:section-title, 'NURSING CONSIDERATIONS')))
					
					and not(
					contains(ce:section-title, 'FIXED-COMBINATION') or contains(ce:section-title, '♦ CLASSIFICATION')
					))
				]
				| ce:section[parent::ce:section[parent::ce:section[@role='sharp']]]" mode="sections"/>
			</xsl:when>
			
			<!-- HLH monograph corpus -->
			<xsl:when test="$source_structure='list'">
				<xsl:apply-templates select="ce:para[ce:display]/ce:list" mode="sections"/>
			</xsl:when>
		</xsl:choose>


		<!-- Here for verifying nothing was omitted -->
		<xsl:choose>
			<!-- catches missing items from the top sections on down; sometimes these will be recognized heads but with incorrect punctuation -->
			<xsl:when test="$devmode = 'Y'">
				<xsl:apply-templates select="descendant::ce:section[
					count(ancestor::ce:section[ce:section-title]) = 1 and
					not(contains(ce:section-title, 'ACTION:') or contains(ce:section-title, 'ACTIONS:') or ce:section-title = 'ACTION'
					or contains(ce:section-title, 'USE:') or contains(ce:section-title, 'USES:')
					or contains(ce:section-title, 'CONTRAINDICATION:') or contains(ce:section-title, 'CONTRAINDICATIONS:') or ce:section-title[.='CONTRAINDICATIONS']
					or contains(ce:section-title, 'CAUTION:') or contains(ce:section-title, 'CAUTIONS:') or normalize-space(ce:section-title) = 'PRECAUTIONS'
					or contains(ce:section-title, 'DOSAGES AND ROUTES') or contains(ce:section-title, 'DOSAGES AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTES') or ce:section-title = 'AVAILABILITY (Rx)' or ce:section-title= 'AVAILABILITY (OTC)' or ce:section-title= 'AVAILABILITY'
					or contains(ce:section-title, 'ADVERSE EFFECT') or contains(ce:section-title, 'ADVERSE EFFECTS') or contains(ce:section-title, 'ADVERSE REACTIONS')
					or contains(ce:section-title, 'SIDE EFFECT') or contains(ce:section-title, 'SIDE EFFECTS')
					or contains(ce:section-title, 'KINETIC') or contains(ce:section-title, 'KINETICS')
					or contains(ce:section-title, 'PHARMACOKINETIC') or contains(ce:section-title, 'PHARMACOKINETICS')
					or contains(ce:section-title, 'DYNAMIC') or contains(ce:section-title, 'DYNAMICS')
					or contains(ce:section-title, 'PHARMACODYNAMIC') or contains(ce:section-title, 'PHARMACODYNAMICS')
					or contains(ce:section-title, 'INTERACTION') or contains(ce:section-title, 'INTERACTIONS')
					or contains(ce:section-title, 'NURSING CONSIDERATION') or contains(ce:section-title, 'NURSING CONSIDERATIONS')
					or ce:section-title[.='TREATMENT OF OVERDOSE:']
					or ce:section-title[.='TREATMENT OF ANAPHYLAXIS:']
					)
				]" mode="missed"/>
			</xsl:when>

			<!-- displays the original XML at the end of the monograph -->
			<xsl:when test="$devmode = 'full'">
				<source class="devmode"><xsl:copy-of select="*"/></source>
			</xsl:when>
		</xsl:choose>
	</monograph>
	<xsl:value-of select="$newline"/>
</xsl:template>

<!-- group monographs (NDG, maybe elsewhere) -->
<xsl:template match="ce:section[ce:section-title and @role='sharp'] | ce:section[parent::ce:section[ce:section-title and @role='sharp'] and ce:section-title]" mode="alpha">
	<group>
		<xsl:attribute name="id"><xsl:value-of select="concat('g', '_REPLACE_ME__')"/></xsl:attribute><!--count(preceding-sibling::ce:section) + 1-->
		<xsl:apply-templates select="ce:section-title" mode="group_name"/>
		<xsl:apply-templates select="ce:section" mode="#current"/>
	
	</group>
</xsl:template>

<!-- separated to improve support for other titles -->
<xsl:template name="monograph_attr">
		<xsl:attribute name="id"><xsl:value-of select="concat('m', '_REPLACE_ME__')"/></xsl:attribute><!--count(preceding-sibling::ce:section) + 1-->
		<xsl:attribute name="status">active</xsl:attribute>
		<xsl:if test="descendant::ce:link/@locator='icon0524-nclex'">
			<xsl:attribute name="nclex">yes</xsl:attribute>
		</xsl:if>
		<xsl:if test="descendant::ce:link/@locator[.='icon0523-rarelyusedbar' or .='icon07-9780323448260' or .='icon08-9780323448260']">
			<xsl:attribute name="ru">yes</xsl:attribute>
		</xsl:if>
		<xsl:if test="descendant::ce:link/@locator[.='icon0522-highalertbar' or .='icon06-9780323448260' or .='icon08-9780323448260']
				or ancestor::ce:section[@role='sharp']/ce:section-title//ce:link/@locator[.='icon0522-highalertbar' or .='icon06-9780323448260' or .='icon08-9780323448260']">
			<xsl:attribute name="ha">yes</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="ce:section-title" mode="mono_name"/>
</xsl:template>

<!-- in devmode, catches missing items from the top sections on down -->
<xsl:template match="*" mode="missed">
	<missed class="devmode">
		<xsl:apply-templates select="." mode="sections"/>
	</missed>
</xsl:template>















<xsl:template match="ce:section-title" mode="group_name">
	<group_title><xsl:apply-templates mode="mono_name"/></group_title>
</xsl:template>

<xsl:template match="ce:section-title" mode="mono_name">
	<mono_name><xsl:apply-templates mode="mono_name"/></mono_name>
</xsl:template>

<xsl:template match="ce:emphasis" mode="mono_name">
	<emphasis>
		<xsl:attribute name="style"><xsl:value-of select="@style"/></xsl:attribute>
		<xsl:attribute name="alert"></xsl:attribute>
		<xsl:apply-templates mode="mono_name"/>
	</emphasis>
</xsl:template>

<!-- TODO: match filenames to alert types -->
<!-- TODO: remove this temporary rule -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0526-drugspecifics']" mode="#all"/>
<!-- "nclex" is a monograph attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0524-nclex']" mode="mono_name"/>
<!-- "rarely used" is a monograph attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0523-rarelyusedbar' or ce:link/@locator = 'icon07-9780323448260']" mode="mono_name"/>
<!-- "high alert" is a monograph attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0522-highalertbar' or ce:link/@locator = 'icon06-9780323448260']" mode="mono_name"/>
<!-- "rarely used" and "high alert" are both monograph attributes; this icon combines them -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon08-9780323448260']" mode="mono_name"/>

<!-- drugspecifics used as qualifier on sec_title -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0526-drugspecifics']" mode="#all"/>

<!-- nurse alert is an emphasis attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0521-nursealert' or ce:link/@locator = 'icon0521-9780323448260' or ce:link/@locator = 'icon01-9780323448260']" mode="#all"/>

<!-- country might appear inside other places besides obvious drug names, but we only have to support Canada; FIX_STRUCTURE: content inside country is duplicated outside -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon0520-canadian' or ce:link/@locator = 'icon0520-9780323448260' or ce:link/@locator = 'icon03-9780323448260']" mode="#all">
	<xsl:call-template name="country">
		<xsl:with-param name="name">
			<xsl:value-of select="concat('FIX_STRUCTURE', normalize-space(preceding-sibling::text()[1]))"/>
		</xsl:with-param>
		<xsl:with-param name="country" select="ce:link/@locator"/>
	</xsl:call-template>

</xsl:template>


<!-- all other images are flagged as errors  -->
<xsl:template match="ce:inline-figure" mode="#all">
	<error type="inline-figure"><xsl:value-of select="ce:link/@locator"/></error>
</xsl:template>

<!-- ignore empty para -->
<xsl:template match="ce:para[not(node())]" mode="#all"/>



<!-- info element calls its own children -->
<xsl:template name="info">
	<info>
		<xsl:call-template name="get_pronunciation"/>
		<xsl:call-template name="get_tradenames"/>
		<xsl:apply-templates select="ce:section[contains(ce:section-title, 'FIXED-COMBINATION')]" mode="combination"/>
		<xsl:apply-templates select="ce:para[contains(ce:italic[1], 'Func. class.:')]" mode="func"/>
		<xsl:apply-templates select="ce:para[contains(ce:italic[1], 'Chem. class.:')]" mode="chem"/>
		<xsl:call-template name="preg"/>
		<xsl:apply-templates select="ce:para[contains(upper-case(text()[1]), 'CONTROLLED SUBSTANCE SCHEDULE') or contains(upper-case(ce:bold[1]/text()[1]), 'CONTROLLED SUBSTANCE SCHEDULE')]" mode="schedule"/>
		<xsl:apply-templates select="ce:section[contains(ce:section-title, '♦ CLASSIFICATION')]/ce:para[ce:bold[.='PHARMACOTHERAPEUTIC:']]" mode="pharma"/>
		<xsl:apply-templates select="ce:section[contains(ce:section-title, '♦ CLASSIFICATION')]/ce:para[ce:bold[ends-with(., 'CLINICAL:')]]" mode="clinical"/>
		<xsl:call-template name="get_bbw"/>
		<xsl:if test="$confusion_location='info'">
			<xsl:apply-templates select="ce:para[contains(ce:bold[1], 'Do not confuse:') or starts-with(ce:bold[1], $confusion_prefix)]" mode="confusion"/>
		</xsl:if>
	</info>	
</xsl:template>

<!-- BEGIN children of info element -->
<xsl:template match="ce:para" mode="pronunciation">
	<pronunciation><xsl:apply-templates/></pronunciation>
</xsl:template>

<xsl:template match="ce:para" mode="tradenames">
	<tradenames>
	<xsl:call-template name="tradenames">
		<!--<xsl:with-param name="text_nodes" select="text()"/>-->
		<!-- countries indicated with icons; NDG: drugs in ce:bold -->
		<xsl:with-param name="countries" select="ce:inline-figure/ce:link/@locator | ce:bold/ce:inline-figure/ce:link/@locator"/>
	</xsl:call-template></tradenames>
</xsl:template>


<!-- tuned to Kizior -->
<xsl:template name="get_bbw">
	<xsl:apply-templates select="ce:para[ce:bold='█ BLACK BOX ALERT █']" mode="bbw"/>
</xsl:template>

<!-- tuned to Skidmore -->
<xsl:template name="get_pronunciation">
	<xsl:apply-templates select="ce:para[position()=1 and starts-with(., '(')]" mode="pronunciation"/>
</xsl:template>

<!-- tuned to Skidmore -->
<xsl:template name="get_tradenames">
	<xsl:apply-templates select="ce:para[(position()=1 or position()=2) and (not(starts-with(., '(')) and not(contains(ce:italic[1], 'Func. class.:')) and not(contains(ce:italic[1], 'Chem. class.:')))]" mode="tradenames"/>

</xsl:template>

<!--
					<ce:para id="p0085">Extina, Ketoderm <ce:inline-figure>
							<ce:link id="lnk0015" locator="icon0520-9780323448260"/>
						</ce:inline-figure>, Ketozole, Nizoral, Nizoral A-D, Xolegel</ce:para>
-->

<!-- tradenames are split on comma (see also drugnames) -->
<xsl:template name="tradenames">
	<!-- contains portion of text currently being processed, it is progressively shortened by splitting on comma -->
	<xsl:param name="names" select="translate(., '()', '')"/>
	<!-- contains original string of text with drug names to compare against $names -->
	<xsl:param name="text_nodes" select="."/>
	<!-- contains @locator strings for country icons or is false when not present -->
	<xsl:param name="countries" select="false()"/>

	<tradename>
		<!--<xsl:attribute name="id">
			<xsl:value-of select="concat('tn', generate-id(.), (string-length($names) - string-length(translate($names, ',', ''))))"/>
		</xsl:attribute>-->
		<xsl:attribute name="cpid" select="'_CPID__'"/><!--TESTING:<a><xsl:value-of select="$text_nodes"/>::<xsl:value-of select="concat(normalize-space(substring-before($names, ',')), '|', translate($text_nodes, '&#8196;', '_'), '|')"/></a>-->
		<xsl:choose>
			<!-- Skidmore: #x2004 = #8196 = ' ' = three-per-em space; Kizior: #2006 = ' ' = six-per-em space; occasionally used: ' ' = space -->
			<xsl:when test="boolean($countries) and ((contains($names, ',') and (ends-with(normalize-space(substring-before($names, ',')), '&#x2004;') or ends-with(normalize-space(substring-before($names, ',')), '&#x2006;') or ends-with(substring-before($names, ','), ' '))) or (not(contains($names, ',')) and (ends-with(normalize-space($names), '&#x2004;') or ends-with(normalize-space($names), '&#x2006;') or ends-with($names, ' '))))">
				<xsl:call-template name="country">
					<xsl:with-param name="name">
						<xsl:choose>
							<xsl:when test="contains($names, ',')">
								<xsl:value-of select="normalize-space(substring-before($names, ','))"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="normalize-space($names)"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
					<xsl:with-param name="country" select="$countries"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="contains($names, ',')">
				<xsl:call-template name="drugname">
					<xsl:with-param name="name">
						<xsl:value-of select="normalize-space(substring-before($names, ','))"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="drugname">
					<xsl:with-param name="name">
						<xsl:value-of select="normalize-space($names)"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</tradename>
	<xsl:if test="contains($names, ',')">
		<xsl:call-template name="tradenames">
			<xsl:with-param name="names" select="substring-after($names, ',')"/>
			<xsl:with-param name="text_nodes" select="$text_nodes"/>
			<xsl:with-param name="countries" select="$countries"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

<xsl:template name="drugname">
	<xsl:param name="name" select="."/>
	<xsl:value-of select="$name"/>
</xsl:template>

<!-- generic drug names are split on comma (see also tradenames); generic usually, type=trade is usually handled by tradenames template -->
<xsl:template name="drugnames">
	<!-- contains portion of text currently being processed, it is progressively shortened by splitting on comma -->
	<xsl:param name="names" select="."/>
	<!-- contains original string of text with drug names to compare against $names -->
	<xsl:param name="text_nodes" select="."/>
	<!-- DO_NOT_SPLIT_ON_ANYTHING if no splitting should take place -->
	<xsl:param name="separator" select="','"/>
	<!-- contains @locator strings for country icons or is false when not present -->
	<xsl:param name="countries" select="false()"/>
	<xsl:param name="parent_node" select="false()"/>

	<drug>
		<xsl:attribute name="type">
			<xsl:choose>
				<!-- this is an educated guess that tradenames have Proper Noun casing -->
				<xsl:when test="matches($names[normalize-space()], '^\s*[A-Z][a-z]')">trade</xsl:when>
				<xsl:otherwise>generic</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:attribute name="refid" select="'_REFID__'">
			<!--<xsl:choose>
				<xsl:when test="boolean($parent_node)">
					<xsl:value-of select="concat(generate-id($parent_node), position())"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="concat(generate-id(.), (string-length($names) - string-length(translate($names, $separator, ''))))"/>
				</xsl:otherwise>
			</xsl:choose>-->
		</xsl:attribute>
		<xsl:choose>
			<xsl:when test="boolean($countries) and normalize-space(substring-before($names, $separator)) = normalize-space(substring-after($text_nodes, $separator))">
				<xsl:call-template name="country">
					<xsl:with-param name="name">
						<xsl:choose>
							<xsl:when test="contains($names, $separator)">
								<xsl:value-of select="normalize-space(substring-before($names, $separator))"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="normalize-space($names)"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
					<xsl:with-param name="country" select="$countries"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="contains($names, $separator)">
				<xsl:value-of select="normalize-space(substring-before($names, $separator))"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="normalize-space($names)"/>
			</xsl:otherwise>
		</xsl:choose>
	
	</drug>
	<xsl:if test="contains($names, $separator)">
		<xsl:call-template name="drugnames">
			<xsl:with-param name="names" select="substring-after($names, $separator)"/>
			<xsl:with-param name="text_nodes" select="$text_nodes"/>
			<xsl:with-param name="separator" select="$separator"/>
			<xsl:with-param name="countries" select="$countries"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>


<!-- wraps a tradename in its country code -->
<xsl:template name="country">
	<xsl:param name="name" select="."/>
	<xsl:param name="country" select="."/> <!-- TODO: this contains every country icon in the nodeset and we keep looking at the first one; good thing it's always Canada -->

	<!-- first case is typical Canada, second is for cases when Canada is also a high alert (seen in Skidmore Appendix A); NDG places CAN in drugspecifics as [2] -->
	<xsl:choose>
		<xsl:when test="contains($country[1], 'icon0520-canadian') or ($country[1]='icon0526-drugspecifics' and $country[2]='icon0520-canadian')
				 or contains($country[1], 'icon0520-9780323448260') or contains($country[1], 'icon03-9780323448260') or contains($country[1], 'icon02-canadian-9780323525091')
				 or (contains($country[1], 'icon01-9780323448260') and contains($country[2], 'icon03-9780323448260'))">
			<country>
				<xsl:attribute name="code">CAN</xsl:attribute>
				<xsl:call-template name="drugname">
					<xsl:with-param name="name">
						<xsl:value-of select="normalize-space($name)"/>
					</xsl:with-param>
				</xsl:call-template>
			</country>
		</xsl:when>
		<!-- drugspecifics without country -->
		<xsl:otherwise>
			<xsl:call-template name="drugname">
				<xsl:with-param name="name">
					<xsl:value-of select="normalize-space($name)"/>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!-- these exclude the label applied in Book DTD -->
<xsl:template match="ce:para" mode="func">
	<class type="func"><xsl:apply-templates select="*[not(contains(., 'Func. class.'))]|text()"/></class>
</xsl:template>
<xsl:template match="ce:para" mode="chem">
	<class type="chem"><xsl:apply-templates select="*[not(contains(., 'Chem. class.'))]|text()"/></class>
</xsl:template>
<xsl:template match="ce:para" mode="pharma">
	<class type="pharma"><xsl:apply-templates select="*[not(contains(., 'Chem. class.'))]|text()"/></class>
</xsl:template>
<xsl:template match="ce:para" mode="clinical">
	<class type="clinical"><xsl:apply-templates select="*[not(contains(., 'Chem. class.'))]|text()"/></class>
</xsl:template>

<!-- BEGIN schedule rules -->
<xsl:template match="ce:para" mode="schedule">
	<xsl:variable name="schedule_num" select="tokenize(.//text(), '\s')"/>
	<class type="schedule">Controlled Substance Schedule <xsl:for-each select="tokenize(.//text(), ' ')"><xsl:call-template name="schedule_num"/></xsl:for-each>
	<xsl:if test="contains(.//text(), '(')">
		<xsl:value-of select="concat(' (', substring-after(.//text(), '('))"/>
	</xsl:if>
	</class>
</xsl:template>

<xsl:template name="schedule_num">
	<xsl:variable name="num" select="translate(., ',', '')"/>
	<xsl:if test="$num='I' or $num='II' or $num='III' or $num='IV' or $num='V' or number($num) = number($num)">
		<schedule>
			<xsl:attribute name="num">
				<xsl:choose>
					<xsl:when test="$num='I'">1</xsl:when>
					<xsl:when test="$num='II'">2</xsl:when>
					<xsl:when test="$num='III'">3</xsl:when>
					<xsl:when test="$num='IV'">4</xsl:when>
					<xsl:when test="$num='V'">5</xsl:when>
					<xsl:when test="number($num) = number($num)"><xsl:value-of select="."/></xsl:when>
				</xsl:choose>
			</xsl:attribute>
			<xsl:value-of select="$num"/>
		</schedule>
	</xsl:if>
</xsl:template>
<!-- END schedule rules -->

<!-- BEGIN pregnancy rules -->
<xsl:template name="preg">
	<xsl:if test="descendant::ce:section[contains(ce:section-title, 'CONTRAINDICATIONS:')]//ce:para[contains(., $preg_term)] or descendant::ce:para/ce:bold[contains(., $preg_term)]">
		<class type="preg"><xsl:text>Pregnancy category</xsl:text>
			<xsl:apply-templates select="descendant::ce:section[contains(ce:section-title, 'CONTRAINDICATIONS:')]//ce:para[contains(., $preg_term)] | descendant::ce:para/ce:bold[contains(., $preg_term)]/parent::ce:para" mode="preg"/>
		</class>
	</xsl:if>
</xsl:template>
<xsl:template match="ce:para" mode="preg">
	<xsl:apply-templates select="descendant::text()" mode="preg_cat"/>
</xsl:template>
<xsl:template match="text()" mode="preg_cat"/>
<!-- NDR prefixed with $preg_term; NDG splits $preg_term and category -->
<xsl:template match="text()[contains(., $preg_term) and not(ends-with(., $preg_term))]" mode="preg_cat">
	<xsl:variable name="cat" select="substring-before(substring-after(.,$preg_term), ')')"/>
	<xsl:text> </xsl:text>
	<preg>
		<xsl:attribute name="cat"><xsl:value-of select="$cat"/></xsl:attribute>
		<xsl:value-of select="$cat"/>
	</preg>
</xsl:template>
<!-- NDG-only rule -->
<xsl:template match="text()[parent::ce:bold and not(contains(., $preg_term))]" name="preg_cat_splitter" mode="preg_cat">
	<xsl:param name="text" select=".[normalize-space()]"/>
	<xsl:param name="separator" select="''"/>

	<xsl:variable name="cat">
		<xsl:choose>
			<xsl:when test="starts-with($text, 'Unknown')">
				<xsl:text>UK</xsl:text>
			</xsl:when>
			<xsl:when test="contains($text, ' ')">
				<xsl:value-of select="substring-before($text, ' ')"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:value-of select="$separator"/><xsl:text> </xsl:text>
	<preg>
		<xsl:attribute name="cat"><xsl:value-of select="$cat"/></xsl:attribute>
		<xsl:choose>
			<xsl:when test="contains($text, ',')">
				<xsl:value-of select="substring-before($text, ',')"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</preg>
	<xsl:if test="contains($text, ',')">
		<xsl:call-template name="preg_cat_splitter">
			<xsl:with-param name="text" select="substring-after($text, ', ')[normalize-space()]"/>
			<xsl:with-param name="separator" select="','"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>
<!-- END pregnancy rules -->
<!-- END children of info element -->


<!--
		<ce:para id="p15750">
			<ce:bold>Do not confuse: clonazePAM</ce:bold>/LORazepam/clorazepate/cloNIDine <ce:bold>KlonoPIN</ce:bold>/cloNIDine
		</ce:para>
-->

<!-- Do Not Confuse paragraph -->
<xsl:template match="ce:para" mode="confusion">
	<xsl:variable name="confusion_node"><xsl:value-of select="ce:bold"/></xsl:variable>
	<xsl:variable name="parent_node"><xsl:copy-of select="."/></xsl:variable>
	<section type="confusion">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<para>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>
			<confusion>
			<xsl:choose>
				<!-- keeps entirety of text in single element -->
				<xsl:when test="$confusion_style = 'sentence'">
					<xsl:apply-templates mode="#current"/>
				
				</xsl:when>
				<!-- breaks text into drug pairings -->
				<xsl:otherwise>
					<xsl:for-each select="tokenize(substring-after(string-join(descendant-or-self::text(), ''), $confusion_prefix), '/')">
						<xsl:choose>
							<!-- confusion sets are separated by spaces, but some drug names contain spaces; this tests against the bold-highlighted first term -->
							<xsl:when test="contains(., ' ') and (contains($confusion_node, substring-before(., ' ')) or contains($confusion_node, substring-after(., ' ')))">
								<xsl:for-each select="tokenize(., ' ')">
								<!-- this is the easiest way to start a new confusion set since they're only separated by spaces -->
								<xsl:if test="contains($confusion_node, .)">
									<xsl:text disable-output-escaping="yes">
								&lt;/confusion&gt;
								&lt;confusion&gt;
									</xsl:text>
								</xsl:if>
								<xsl:choose>
									<!-- this is an educated guess that tradenames have Proper Noun casing -->
									<xsl:when test="matches(., '^[A-Z][a-z]')">
										<drug type="trade">
											<!--<xsl:attribute name="id">
												<xsl:value-of select="concat('tn', generate-id($parent_node), position())"/>
											</xsl:attribute>-->
											<xsl:attribute name="refid" select="'_REFID__'"/>
											<xsl:value-of select="."/>
										</drug>
									</xsl:when>
									<xsl:otherwise>
										<xsl:call-template name="drugnames">
											<xsl:with-param name="parent_node" select="$parent_node"/>
										</xsl:call-template>
									</xsl:otherwise>
								</xsl:choose>
								</xsl:for-each>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<!-- this is an educated guess that tradenames have Proper Noun casing -->
									<xsl:when test="matches(., '^[A-Z][a-z]')">
										<drug type="trade">
											<!--<xsl:attribute name="id">
												<xsl:value-of select="concat('tn', generate-id($parent_node), position())"/>
											</xsl:attribute>-->
											<xsl:attribute name="refid" select="'_REFID__'"/>
											<xsl:value-of select="."/>
										</drug>
									</xsl:when>
									<xsl:otherwise>
										<xsl:call-template name="drugnames">
											<xsl:with-param name="parent_node" select="$parent_node"/>
										</xsl:call-template>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>			
					</xsl:for-each>
				
				</xsl:otherwise>
			</xsl:choose>
			</confusion>
		</para>
	</section>
</xsl:template>

<xsl:template match="ce:bold" mode="confusion">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<xsl:template match="text()" mode="confusion">
	<xsl:value-of select="replace(., $confusion_prefix, '')"/>
</xsl:template>


<!-- BEGIN sections, subsections and subsubsections -->
<!-- TODO: break section block into called template to more elegantly handle include/exclude of sections -->
<xsl:template match="ce:section|ce:para" name="sections" mode="sections">
	<xsl:variable name="section_title_normalized" select="translate(normalize-space(translate(lower-case(ce:section-title), ' ♦​', ' ')), ' ', '_')"/><!-- en space; Kizior diamond; zero-width space -->
	<xsl:variable name="section_title">
		<xsl:choose>
			<!-- correction of errors in source -->
			<xsl:when test="contains($section_title_normalized, 'iv_incompatabilities')">
				<xsl:value-of select="'iv_incompatibilities'"/>
			</xsl:when>
			<xsl:when test="contains($section_title_normalized, 'fixed_combination(s)')">
				<xsl:value-of select="'fixed-combination(s)'"/>
			</xsl:when>
			
			<!-- no correction needed -->
			<xsl:otherwise>
				<xsl:value-of select="$section_title_normalized"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<!-- TODO: recurse this into a real title caser -->
	<!-- the much simpler '(^|\W)(otc|rx|iv|po|sq)(\W|$)', '$1\U$2$3' cannot be used because XPATH doesn't support anything more than backreferences in the replacement pattern -->
	<xsl:variable name="section_title_cased" select="replace(replace(replace(replace(replace(translate(concat(upper-case(substring($section_title, 1, 1)), substring($section_title, 2)), '_', ' '), 'otc', 'OTC', 'i'), 'rx', 'Rx', 'i'), '(^|\W)iv(\W|$)', '$1IV$2', 'i'), 'po', 'PO', 'i'), 'sq', 'SQ', 'i')"/>

	<!-- defined section types (other heads are free-form sections); adding a $type here should correspond to adding it to <monograph> above -->
	<!-- DTD-defined $types=actions | kinetics | dynamics | uses | cautions | doses | effects | interactions | considerations | none | contra | intravenous | drugspecifics -->
	<xsl:variable name="type">
		<xsl:choose>
			<!-- also set in rule below for Therapeutic outcomes -->
			<xsl:when test="$section_title = 'action'">
				<xsl:text>actions</xsl:text>
			</xsl:when>
			<xsl:when test="ends-with($section_title, 'precautions')">
				<xsl:text>cautions</xsl:text>
			</xsl:when>
			<xsl:when test="contains($section_title, 'contraindication')">
				<xsl:text>contra</xsl:text>
			</xsl:when>
			<xsl:when test="(contains($section_title, 'dosage') and contains($section_title, 'route')) or (contains($section_title, 'implementation')) or $section_title = 'availability_(rx)' or $section_title= 'availability_(otc)' or $section_title= 'availability'">
				<xsl:text>doses</xsl:text>
			</xsl:when>
			<xsl:when test="contains($section_title, 'adverse_effect') or contains($section_title, 'adverse_reaction') or contains($section_title, 'side_effect')">
				<xsl:text>effects</xsl:text>
			</xsl:when>
			<xsl:when test="contains($section_title, 'kinetic') or contains($section_title, 'pharmacokinetic')">
				<xsl:text>kinetics</xsl:text>
			</xsl:when>
			<xsl:when test="contains($section_title, 'dynamic') or contains($section_title, 'pharmacodynamic')">
				<xsl:text>dynamics</xsl:text>
			</xsl:when>
			<xsl:when test="contains($section_title, 'nursing_consideration')">
				<xsl:text>considerations</xsl:text>
			</xsl:when>
			<!-- TODO: are these really all IV? -->
			<xsl:when test="contains($section_title, 'iv_compatibility') or contains($section_title, 'iv_compatibilities') or ce:section-title/ce:inline-figure/ce:link[@locator='icon08-iv-9780323525091'] or contains($section_title, 'iv_incompatibility') or contains($section_title, 'iv_incompatibilities') or ce:inline-figure/ce:link[@locator='icon09-iv-compat-9780323525091']">
				<xsl:text>intravenous</xsl:text>
			</xsl:when>
			<!-- add new top-level section_titles to ignore here -->
			<!-- TODO: Kizior has new ones here and they may be wrong; Skidmore NDG does too -->
			<xsl:when test="$section_title = '' or substring-before($section_title, ':') = 'treatment_of_overdose' or substring-before($section_title, ':') = 'treatment_of_hypersensitivity' or substring-before($section_title, ':') = 'treatment_of_anaphylaxis' 
			 or $section_title = 'therapeutic_outcome'  or $section_title = 'therapeutic_outcomes' or substring-before($section_title, ':') = 'therapeutic_outcome'  or substring-before($section_title, ':') = 'therapeutic_outcomes' 
			 or $section_title = 'idiosyncratic_reaction' or $section_title = 'idiosyncratic_reactions' or substring-before($section_title, ':') = 'idiosyncratic_reaction' or substring-before($section_title, ':') = 'idiosyncratic_reactions'
			 or $section_title = 'treatment_of_ingestions'
			 or $section_title = 'fixed-combination(s)' or $section_title = 'classification' or $section_title = 'lifespan_considerations' or $section_title = 'administration/handling' or contains($section_title, 'hepatic_enzymes')">
				<xsl:text>none</xsl:text>
			</xsl:when>
			<!-- grab section head text and ensure it always has an "s" at the end; helps identify uncaught section titles by outputting "nones" -->
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="contains($section_title, 's:')">
						<xsl:value-of select="substring-before($section_title, 's:')"/>
					</xsl:when>
					<xsl:when test="contains($section_title, ':')">
						<xsl:value-of select="substring-before($section_title, ':')"/>
					</xsl:when>
					<xsl:when test="substring($section_title, string-length($section_title)) = 's'">
						<xsl:value-of select="substring($section_title, 1, string-length($section_title) - 1)"/>
					</xsl:when>
					<!-- if we don't recognize it, probably it is a bespoke header and not a predefined type -->
					<xsl:otherwise>
						<!--FOR TESTING OUTPUT: xsl:value-of select="$section_title"/-->
						<xsl:text>none</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:text>s</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<section type="{$type}">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:choose>
			<xsl:when test="string-length($section_title_cased) &gt; 0">
				<sec_title>
					<xsl:value-of select="normalize-space($section_title_cased)"/>
				</sec_title>
				<xsl:apply-templates select="ce:section|ce:para" mode="subsection"/>
			</xsl:when>

			<!-- eg, "See elsewhere" para -->
			<xsl:when test="local-name(.) = 'para'">
				<xsl:apply-templates select="." mode="subsection"/>
			</xsl:when>
		</xsl:choose>
	</section>
</xsl:template>

<!-- skip these -->
<xsl:template match="ce:section[not(ce:section-title) and descendant::ce:section/ce:section-title[.='Therapeutic outcome:']][not(ce:section/ce:section-title) or not(parent::ce:section/ce:section-title)]" mode="sections"/>

<!-- Therapeutic outcomes are always part of actions, but that upper level might be missing, this forces it into the correct @type, repeating @type from above ce:section rule -->
<xsl:template match="ce:section[ce:section-title[.='Therapeutic outcome:']][count(ancestor::ce:section/ce:section-title) = 1]" mode="sections">
	<section type="actions">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:call-template name="sections"/>
	</section>
</xsl:template>


<xsl:template match="ce:section" mode="subsection">
	<section type="none">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="subsection"/>
	</section>
</xsl:template>


<!-- Skidmore NDG places inside effects as tables within ce:section -->
<xsl:template match="ce:section[ce:section-title[starts-with(upper-case(.), 'KINETIC') or starts-with(upper-case(.), 'PHARMACOKINETIC')]]" mode="subsection">
	<section type="kinetics">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</section>
</xsl:template>
<xsl:template match="ce:section[ce:section-title[starts-with(upper-case(.), 'DYNAMIC') or starts-with(upper-case(.), 'PHARMACODYNAMIC')]]" mode="subsection">
	<section type="dynamics">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</section>
</xsl:template>

<!-- Skidmore NDG has a lot of these matching these patterns, untested in other titles -->
<xsl:template match="ce:section[ce:section-title[matches(., '(^|\W)IV(\W|$)') and (matches(., '(^|\W)route(\W|$)', 'i') or contains(., 'INF') or contains(lower-case(.), 'infusion') or contains(lower-case(.), 'direct') or contains(., 'administration'))]]" mode="subsection">
	<section type="intravenous">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</section>
</xsl:template>

<!-- Skidmore NDG marker -->
<xsl:template match="ce:section[ce:section-title/ce:inline-figure/ce:link[@locator='icon0526-drugspecifics']]" mode="subsection">
	<section type="drugspecifics">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</section>
</xsl:template>

<xsl:template match="ce:section-title" mode="subsection">
	<sec_title><xsl:apply-templates/></sec_title>
</xsl:template>

<xsl:template match="ce:section-title[descendant-or-self::ce:link[@locator='icon0526-drugspecifics']]" mode="subsection">
	<xsl:param name="names" select="text()[normalize-space()]"/>
	<xsl:param name="text_nodes" select="text()[normalize-space()]"/>
	<xsl:variable name="parent_node"><xsl:copy-of select="."/></xsl:variable>

	<sec_title>
		<xsl:call-template name="drugnames">
			<xsl:with-param name="names" select="$names"/>
			<xsl:with-param name="separator" select="'DO_NOT_SPLIT_ON_ANYTHING'"/>
			<xsl:with-param name="text_nodes" select="$text_nodes"/>
			<xsl:with-param name="parent_node" select="$parent_node"/>
			<xsl:with-param name="countries" select="ce:inline-figure/ce:link/@locator | ce:bold/ce:inline-figure/ce:link/@locator"/>
		</xsl:call-template>
	</sec_title>
</xsl:template>

<!-- if there is a child inside this section that is not bold we should not assume this is lifethreat, it is probably just bold or a header -->
<xsl:template match="ce:section-title[ancestor::ce:section/ce:section-title[contains(lower-case(.), 'dosage')]][parent::ce:section/descendant-or-self::text()[normalize-space()][not(ancestor::ce:section-title or ancestor::ce:bold or ancestor::ce:label)]]" mode="subsection">
	<sec_title><xsl:apply-templates/></sec_title>
</xsl:template>
<!-- with some exceptions, these are all lifethreat subheads because the first entry and everything following is also a lifethreat (rarely more than one) -->
<xsl:template match="ce:section-title[ancestor::ce:section/ce:section-title[contains(lower-case(.), 'dosage')]][not(parent::ce:section/descendant-or-self::text()[normalize-space()][not(ancestor::ce:section-title or ancestor::ce:bold or ancestor::ce:label)])]" mode="subsection">
	<sec_title><emphasis alert="lifethreat"><xsl:apply-templates/></emphasis></sec_title>
</xsl:template>

<xsl:template match="ce:section" mode="combination">
	<combination>
		<xsl:attribute name="id" select="concat('cb', '_REPLACE_ME__')"/>
		<!-- this normalizes the section title for the book -->
		<sec_title>Fixed-Combination(s)</sec_title>
		<xsl:apply-templates select="ce:section|ce:para" mode="subsection"/>
	</combination>
</xsl:template>


<xsl:template match="ce:para" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="subsection"/>
	</para>
</xsl:template>

<xsl:template match="ce:para[ce:display/ce:table]" mode="subsection">
	<xsl:apply-templates select="ce:display" mode="subsection"/>
</xsl:template>

<xsl:template match="ce:para[ce:display/ce:table and text()[normalize-space()]]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates select="node()[not(local-name()='display')]" mode="subsection"/>
	</para>
	<xsl:apply-templates select="ce:display" mode="subsection"/>
</xsl:template>

<!-- lists inside paras makes my skin crawl, but that's how the DTD does it -->
<xsl:template match="ce:para[ce:list]" mode="subsection">
	<xsl:choose>
		<xsl:when test="ce:list[not(descendant::ce:list) and not(descendant::ce:bold) and not(descendant::ce:section)]">
			<para>
				<xsl:attribute name="id">
					<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
				</xsl:attribute>
				<xsl:apply-templates mode="subsection"/>
			</para>
		</xsl:when>
		<!-- NDG: added text, * and changed descendant::ce:list-item to single level only -->
		<xsl:otherwise>
			<xsl:apply-templates select="text()[normalize-space()] | *[not(local-name()='list')] | ce:list/ce:list-item" mode="subsection"/>
			<!-- DELETE?:<xsl:apply-templates select="descendant::ce:list-item" mode="wraplistitem"/>-->
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

<!-- para here has both text and a bbw -->
<xsl:template match="ce:para[text()[normalize-space()] and ce:display[contains(upper-case(ce:textbox/ce:textbox-head/ce:title), 'BLACK BOX WARNING')]]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates select="*[not(contains(upper-case(ce:textbox/ce:textbox-head/ce:title), 'BLACK BOX WARNING'))] | text()[normalize-space()]" mode="subsection"/>
	</para>
	<xsl:apply-templates select="ce:display[contains(upper-case(ce:textbox/ce:textbox-head/ce:title), 'BLACK BOX WARNING')]" mode="subsection"/>
</xsl:template>

<!-- para here is a container for child containers only and shouldn't be included as an element in the output -->
<xsl:template match="ce:para[not(text()[normalize-space()]) and ce:display[contains(upper-case(ce:textbox/ce:textbox-head/ce:title), 'BLACK BOX WARNING')]]" mode="subsection">
	<xsl:apply-templates mode="subsection"/>
</xsl:template>

<!-- see instead paragraph should be only sibling of mono_name-->
<xsl:template match="ce:para" mode="see">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates/>
	</para>
</xsl:template>

<xsl:template match="ce:cross-ref|ce:intra-ref|ce:inter-ref" mode="#all">
	<xsl:call-template name="see"/>
</xsl:template>

<!-- NDG will have its italicized intra-ref and cross-ref turned inside out -->
<xsl:template match="ce:cross-ref[ce:italic and $see_italic = 'invert'] | ce:intra-ref[ce:italic and $see_italic = 'invert']" mode="#all">
	<emphasis style="italic"><xsl:call-template name="see"/></emphasis>
</xsl:template>

<xsl:template name="see">
	<xsl:variable name="type">
		<xsl:choose>
			<xsl:when test="starts-with(@refid, 's')">section</xsl:when>
			<xsl:when test="starts-with(@refid, 'tfn')">ftnote</xsl:when>
			<xsl:when test="starts-with(@refid, 't')">table</xsl:when>
			<xsl:when test="starts-with(@xlink:href, 'pii:')">molecule</xsl:when>
			<xsl:when test="local-name() = 'inter-ref'">external</xsl:when>
			<xsl:otherwise>atom</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<see>
		<xsl:if test="$devmode='Y'">
			<xsl:attribute name="type" select="$type"/>
		</xsl:if>
		<xsl:attribute name="refid">
			<xsl:choose>
				<xsl:when test="$type='section'">
					<xsl:value-of select="concat('#', @refid)"/>
				</xsl:when>
				<xsl:when test="$type='ftnote'">
					<xsl:value-of select="concat('#', @refid)"/>
				</xsl:when>
				<xsl:when test="$type='table'">
					<!-- t1, t2, etc -->
					<xsl:value-of select="concat('#t', count(ancestor::ce:section[ce:section-title and parent::ce:section[ce:section and not(ce:section-title)]]//ce:table[@id=current()/@refid]/preceding::ce:table[ancestor::ce:section[ce:section-title and parent::ce:section[ce:section and not(ce:section-title)]] = current()/ancestor::ce:section[ce:section-title and parent::ce:section[ce:section and not(ce:section-title)]]]) + 1)"/>		
				</xsl:when>

				<!-- atom and molecule links target a stripped word version of that target's title -->
				<xsl:when test="$type='molecule'">
					<xsl:value-of select="concat('m:', lower-case(replace(., '[^A-Za-z0-9]', '')))"/>
				</xsl:when>
				
				<!-- external link -->
				<xsl:when test="$type='external'">
					<xsl:value-of select="@xlink:href"/>
				</xsl:when>

				<!-- TODO: this should really follow an atom to the mono_name found there to be sure no misspellings, etc arise -->				
				<xsl:otherwise>
					<xsl:value-of select="concat('a:', lower-case(replace(., '[^A-Za-z0-9]', '')))"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:apply-templates select="descendant-or-self::text()[normalize-space()]"/>
	</see>
</xsl:template>


<xsl:template match="ce:list|ce:list-item" mode="subsection">
	<xsl:apply-templates mode="subsection"/>
</xsl:template>

<!-- already wrapped in para; bold indicates possible lifethreat or sec_title; second match picks up a few extra lists, but does not seem to break them -->
<xsl:template match="ce:list[not(descendant::ce:list) and not(descendant::ce:bold) and not(descendant::ce:section)]" mode="#all">
	<list>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('l', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</list>
</xsl:template>

<xsl:template match="ce:list[not(descendant::ce:list) and not(descendant::ce:section)]" mode="subsubsection">
	<list>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('l', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</list>
</xsl:template>


<!-- para will be missing because of bbw -->
<xsl:template match="ce:list[not(descendant::ce:list) and not(descendant::ce:bold) and not(descendant::ce:section) and ../ce:display[contains(upper-case(descendant::ce:title), 'BLACK BOX WARNING')]]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<list>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('l', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>
			<xsl:apply-templates mode="subsection"/>
		</list>
	</para>
</xsl:template>

<!-- DELETE?
<xsl:template match="ce:list-item" mode="wraplistitem">
	<xsl:apply-templates mode="subsection"/>
</xsl:template> -->

<xsl:template match="ce:label" mode="sec_title">
	<label><xsl:apply-templates mode="sec_title"/></label>
</xsl:template>

<xsl:template match="ce:label[parent::ce:list-item and not(following-sibling::ce:para//ce:bold) and not(following-sibling::ce:para//ce:section)]" mode="subsection">
	<label><xsl:apply-templates mode="subsection"/></label>
</xsl:template>

<!-- ignore these, at least in this context -->
<xsl:template match="ce:anchor" mode="#all"/><!-- page markers -->
<xsl:template match="ce:label" mode="subsection"/>
<xsl:template match="ce:bold" mode="subsubsection"/>

<!-- first rule accommodates lifethreat, others skip it; do not change to #all -->
<xsl:template match="ce:list-item[not(descendant::ce:section) and not(descendant::ce:list)]" mode="subsubsection">
	<item><xsl:apply-templates mode="#current"/></item>
</xsl:template>
<xsl:template match="ce:list-item[not(descendant::ce:bold) and not(descendant::ce:section)]" mode="subsection">
	<item><xsl:apply-templates mode="#current"/></item>
</xsl:template>
<xsl:template match="ce:list-item[not(descendant::ce:bold) and not(descendant::ce:section)]" mode="subsubsection">
	<item><xsl:apply-templates mode="#current"/></item>
</xsl:template>

<!-- list element will be missing because a sibling is going to contain a section, but list is required and so is para -->
<xsl:template match="ce:list-item[not(descendant::ce:bold) and not(descendant::ce:section) and ../ce:list-item[descendant::ce:bold or descendant::ce:section]]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<list>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('l', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>
			<item><xsl:apply-templates mode="subsection"/></item>
		</list>
	</para>
</xsl:template>

<xsl:template match="ce:para" mode="subsubsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="subsection"/>
	</para>
</xsl:template>

<xsl:template match="ce:para[parent::ce:list-item]" mode="subsection">
	<section type="none">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!-- generate-id(.) -->
		</xsl:attribute>
		<xsl:choose>
			<!-- probable sec_title; routes are sometimes present in these; if it ends in a comma it's just text -->
			<xsl:when test="ce:bold[1][not(ends-with(., ',') or ends-with(., ';')) and not(preceding-sibling::text()[normalize-space()]) and following-sibling::text()[normalize-space()]]">
				<xsl:if test="preceding-sibling::ce:label">
					<xsl:apply-templates select="preceding-sibling::ce:label" mode="sec_title"/>
				</xsl:if>
				<sec_title>
					<xsl:choose>
						<!-- route is present only in doses; likely values: BUCC, IM, PO, O, PR, S/C, SUBCUT, SL, TOP, V, PV, GEL, SHAMPOO, TOP FOAM, OPHTH, RECT, TD -->
						<xsl:when test="(ancestor::ce:section[contains(ce:section-title, 'DOSAGES AND ROUTES') or contains(ce:section-title, 'DOSAGES AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTE') or contains(ce:section-title, 'DOSAGE AND ROUTES')] or ce:section-title= 'AVAILABILITY (Rx)' or ce:section-title= 'AVAILABILITY (OTC)' or ce:section-title= 'AVAILABILITY')
							 and (not(contains(ce:bold[1], ':')) or string-length(substring-after(ce:bold[1], ':')) &gt; 0)">
							<xsl:value-of select="substring-before(ce:bold[1], ':')"/>
							<route><xsl:choose>
								<xsl:when test="not(contains(ce:bold[1], ':'))"><xsl:value-of select="ce:bold[1]"/></xsl:when>
								<xsl:when test="contains(substring-after(ce:bold[1], ':'), ':')"><xsl:value-of select="substring-before(substring-after(ce:bold[1], ':'), ':')"/></xsl:when>
								<xsl:otherwise><xsl:value-of select="substring-after(ce:bold[1], ':')"/></xsl:otherwise><!-- TODO: this breaks when emphasis is present -->
							</xsl:choose></route>
							<!-- this will place text in the wrong place and we will trust the proofreading process to correct it since XSL cannot -->
							<xsl:apply-templates select="ce:bold[1]/*" mode="#current"/>
						</xsl:when>
						<!-- the whole node is a sec_title, and does not contain a route -->
						<xsl:otherwise>
							<xsl:apply-templates select="ce:bold[1]" mode="#current"/>
						</xsl:otherwise>
					</xsl:choose>
				</sec_title>
				<!-- this will duplicate some content already in route and should be the lowest level of sections -->
				<xsl:if test="ce:bold[position() &gt; 1] or ce:bold[1]/* or text()[normalize-space()]">
					<xsl:call-template name="sublistitem"/>
				</xsl:if>
			</xsl:when>
			<!-- lifethreat -->
			<xsl:when test="not(text()[normalize-space()])">
				<xsl:call-template name="sublistitem"/>
			</xsl:when>
			<!-- not section title -->
			<xsl:otherwise>
				<xsl:if test="preceding-sibling::ce:label">
					<xsl:apply-templates select="preceding-sibling::ce:label" mode="sec_title"/>
				</xsl:if>
				<xsl:call-template name="sublistitem"/>
			</xsl:otherwise>
		</xsl:choose>
	</section>
</xsl:template>

<xsl:template match="ce:para[parent::ce:list-item and not(descendant::ce:bold[not(parent::text()[normalize-space()])]) and not(descendant::ce:section)]" mode="subsection">
	<!-- this should be the lowest level of sections -->
		<para>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>
			<xsl:apply-templates mode="subsubsection"/>
		</para>
</xsl:template>
<xsl:template match="ce:para[parent::ce:list-item and not(descendant::ce:bold[not(parent::text()[normalize-space()])]) and not(descendant::ce:section)]" mode="subsubsection">
	<!-- this should be the lowest level of sections -->
		<para>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>
			<xsl:apply-templates mode="#current"/>
		</para>
</xsl:template>

<!-- this is not a route and will be matched as a sec_title unless forced into a list -->
<xsl:template match="ce:para[parent::ce:list-item and ce:bold[preceding-sibling::ce:inline-figure[ce:link/@locator = 'icon0521-nursealert' or ce:link/@locator = 'icon0521-9780323448260' or ce:link/@locator = 'icon01-9780323448260']] and not(descendant::ce:section) and not(text()[normalize-space()])]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<list>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('l', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>	
			<item>
				<para>
					<xsl:attribute name="id">
						<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
					</xsl:attribute>
					<xsl:apply-templates mode="subsubsection"/>
				</para>
			</item>
		</list>
	</para>
</xsl:template>

<xsl:template match="ce:para" mode="bbw">
	<bbw>
		<xsl:apply-templates select="node()[not(text()='█ BLACK BOX ALERT █')]"/>
	</bbw>
</xsl:template>


<!-- bbw found adjacent to a para containing text -->
<xsl:template match="ce:display[contains(upper-case(ce:textbox/ce:textbox-head/ce:title), 'BLACK BOX WARNING')]|ce:textbox[contains(upper-case(ce:textbox-head/ce:title), 'BLACK BOX WARNING')]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<bbw><xsl:apply-templates select="ce:textbox/ce:textbox-body/ce:sections/ce:para/* | ce:textbox-body/ce:sections/ce:para/* | ce:textbox/ce:textbox-body/ce:sections/ce:para/text()[normalize-space()] | ce:textbox-body/ce:sections/ce:para/text()[normalize-space()]" mode="bbw"/></bbw>
	</para>
</xsl:template>

<!-- bbw found within a list-item para containing text -->
<xsl:template match="ce:display[contains(upper-case(ce:textbox/ce:textbox-head/ce:title), 'BLACK BOX WARNING')]|ce:textbox[contains(upper-case(ce:textbox-head/ce:title), 'BLACK BOX WARNING')]" mode="subsubsection">
	<xsl:value-of select="$newline"/>
	<bbw><xsl:apply-templates select="ce:textbox/ce:textbox-body/ce:sections/ce:para/* | ce:textbox-body/ce:sections/ce:para/* | ce:textbox/ce:textbox-body/ce:sections/ce:para/text()[normalize-space()] | ce:textbox-body/ce:sections/ce:para/text()[normalize-space()]" mode="bbw"/></bbw>
	<xsl:value-of select="$newline"/>
</xsl:template>

<xsl:template name="sublistitem">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<list>
			<xsl:attribute name="id">
				<xsl:value-of select="concat('l', '_REPLACE_ME__')"/><!--generate-id(.)-->
			</xsl:attribute>
			<item>
				<xsl:if test="preceding-sibling::ce:label">
					<label><xsl:apply-templates select="preceding-sibling::ce:label" mode="subsubsection"/></label>
				</xsl:if>
				<para>
					<xsl:attribute name="id">
						<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
					</xsl:attribute>
					<xsl:apply-templates mode="subsubsection"/>
				</para>
			</item>
		</list>
	</para>
</xsl:template>

<xsl:template match="ce:label" mode="subsubsection">
	<xsl:apply-templates mode="subsubsection"/>
</xsl:template>

<!-- END sections, subsections and subsubsections -->










<!-- BEGIN visual style templates -->
<!-- regular set -->
<xsl:template match="ce:bold">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:bolditalic">
  <emphasis style="bold"><emphasis style="italic"><xsl:apply-templates/></emphasis></emphasis>
</xsl:template>
<xsl:template match="ce:inf" mode="#all">
  <emphasis style="inf"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:italic">
  <emphasis style="italic"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- Kizior italicized all intra-ref -->
<xsl:template match="ce:italic[parent::ce:intra-ref]" mode="#all">
  <xsl:apply-templates/>
</xsl:template>
<xsl:template match="ce:sans-serif">
  <emphasis style="sans-serif"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:small-caps" mode="#all">
  <emphasis style="smallcaps"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:sup" mode="#all">
  <emphasis style="sup"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:tallman">
  <emphasis style="tallman"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:underline">
  <emphasis style="underline"><xsl:apply-templates/></emphasis>
</xsl:template>

<!-- subsection set -->
<!-- default is now lifethreat, exceptions follow -->
<xsl:template match="ce:bold" mode="subsection">
  <emphasis alert="lifethreat"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- exceptions to this rule will need to be reset manually, but this avoids Pregnancy Categories, inline and row table heads, BBW (some manual exceptions) -->
<xsl:template match="ce:bold[ancestor::ce:section/ce:section-title[.='CONTRAINDICATIONS'] or ends-with(., ':') or ancestor::entry or ancestor::ce:textbox]" mode="subsection">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- bbw set, reevaluate as subsection and manually create lifethreat nodes -->
<xsl:template match="ce:bold" mode="bbw">
  <xsl:apply-templates select="." mode="subsection"/>
</xsl:template>
<!-- routes should not be marked as lifethreat -->
<xsl:template match="ce:bold[.='IV' or .='BUCC' or .='IM' or .='PO' or .='O' or .='PR' or .='S/C' or .='SUBCUT' or .='SL' or .='TOP' or .='V' or .='PV' or .='GEL' or .='SHAMPOO' or .='TOP FOAM' or .='OPHTH' or .='RECT' or .='TD']" mode="subsection">
	<route><xsl:apply-templates mode="#current"/></route>
</xsl:template>
<!-- route markup is not needed inside bbw -->
<xsl:template match="ce:bold[ancestor::ce:textbox[contains(upper-case(ce:textbox-head/ce:title), 'BLACK BOX WARNING')] and (.='IV' or .='BUCC' or .='IM' or .='PO' or .='O' or .='PR' or .='S/C' or .='SUBCUT' or .='SL' or .='TOP' or .='V' or .='PV' or .='GEL' or .='SHAMPOO' or .='TOP FOAM' or .='OPHTH' or .='RECT' or .='TD')]" mode="subsection">
	<emphasis style="bold"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>

<!-- lifethreat is not found inside tables -->
<xsl:template match="ce:bold[not(parent::*/text()[normalize-space()]) and not(ancestor::entry)]" mode="#all">
  <emphasis alert="lifethreat"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<!-- NDG had lifethreat text preceding the text of a list and it functions more consistently as boldface than a sec_title, but transformed without a para -->
<xsl:template match="ce:bold[not(parent::*/text()[normalize-space()]) and parent::*/ce:list]" mode="subsection">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<emphasis alert="lifethreat"><xsl:apply-templates/></emphasis>
	</para>
</xsl:template>
<xsl:template match="ce:bold[preceding-sibling::ce:inline-figure[ce:link/@locator = 'icon0521-nursealert' or ce:link/@locator = 'icon0521-9780323448260' or ce:link/@locator = 'icon01-9780323448260']]" mode="subsection">
  <emphasis alert="nurse"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:bolditalic" mode="subsection">
  <emphasis style="bold"><emphasis style="italic"><xsl:apply-templates/></emphasis></emphasis>
</xsl:template>
<xsl:template match="ce:italic" mode="subsection">
  <emphasis style="italic"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:sans-serif" mode="subsection">
  <emphasis style="sans-serif"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:tallman" mode="subsection">
  <emphasis style="tallman"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:underline" mode="subsection">
  <emphasis style="underline"><xsl:apply-templates/></emphasis>
</xsl:template>

<!-- subsubsection set -->
<xsl:template match="ce:bold" mode="subsubsection">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- already used as sec_title -->
<xsl:template match="ce:bold[not(ends-with(., ',') or ends-with(., ';')) and not(preceding-sibling::text()[normalize-space()]) and following-sibling::text()[normalize-space()]]" mode="subsubsection"/>
<xsl:template match="ce:bold[preceding-sibling::ce:inline-figure[ce:link/@locator = 'icon0521-nursealert' or ce:link/@locator = 'icon0521-9780323448260' or ce:link/@locator = 'icon01-9780323448260']]" mode="subsubsection">
  <emphasis alert="nurse"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:bolditalic" mode="subsubsection">
  <emphasis style="bold"><emphasis style="italic"><xsl:apply-templates/></emphasis></emphasis>
</xsl:template>
<xsl:template match="ce:inf" mode="subsubsection">
  <emphasis style="inf"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:italic" mode="subsubsection">
  <emphasis style="italic"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:sans-serif" mode="subsubsection">
  <emphasis style="sans-serif"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:tallman" mode="subsubsection">
  <emphasis style="tallman"><xsl:apply-templates/></emphasis>
</xsl:template>
<xsl:template match="ce:underline" mode="subsubsection">
  <emphasis style="underline"><xsl:apply-templates/></emphasis>
</xsl:template>

<xsl:template match="ce:small-caps" mode="mono_name">
  <emphasis style="smallcaps"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- END visual style templates -->






<!-- used to catch pregnancy class indicators -->
<xsl:template match="text()" mode="subsection">
	<xsl:choose>
		<xsl:when test="contains(., $preg_term)">
			<xsl:call-template name="HighlightPreg">
				<xsl:with-param name="stringToSearchIn" select="."/>
				<xsl:with-param name="substringToHighlight" select="$preg_term"/>
				<xsl:with-param name="mode" select="'search'"/>
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:copy-of select="."/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>
<xsl:template match="text()" mode="subsubsection">
	<xsl:choose>
		<xsl:when test="contains(., $preg_term)">
			<preg><xsl:copy-of select="."/></preg>
		</xsl:when>
		<xsl:otherwise>
			<xsl:copy-of select="."/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- TODO: recurse this into a real title caser -->
<xsl:template match="text()[not(matches(., '[a-z]+')) and parent::ce:section-title[count(ancestor::ce:section) &gt; 6] and $titlecase_sections]">
	<!-- the much simpler '(^|\W)(otc|rx|iv|po|sq)(\W|$)', '$1\U$2$3' cannot be used because XPATH doesn't support anything more than backreferences in the replacement pattern -->
	<xsl:value-of select="replace(replace(replace(replace(replace(normalize-space(concat(upper-case(substring(., 1, 1)), lower-case(substring(., 2)))), 'otc', 'OTC', 'i'), 'rx', 'Rx', 'i'), '(^|\W)iv(\W|$)', '$1IV$2', 'i'), 'po', 'PO', 'i'), 'sq', 'SQ', 'i')"/>

</xsl:template>

<!--this is not exactly identical to the HighlightMatches from which it is derived because $substringToHighlight immediately precedes what should be highlighted-->
<xsl:template name="HighlightPreg">
	<xsl:param name="stringToSearchIn"/>
	<xsl:param name="substringToHighlight"/><!--NOTE: no default-->
	<xsl:param name="mode" select="'devmode'"/><!--NOTE: used to toggle search mode -->
	<xsl:variable name="stringToSearchIn-CASE" select="translate($stringToSearchIn, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/>
	<xsl:variable name="substringToHighlight-CASE" select="translate($substringToHighlight, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/>
	<xsl:variable name="before" select="substring-before($stringToSearchIn-CASE, $substringToHighlight-CASE)"/>
	<xsl:variable name="before_length" select="string-length($before)"/>
	<xsl:variable name="after" select="substring-before(substring-after($stringToSearchIn-CASE, $substringToHighlight-CASE), ')')"/>
	<xsl:variable name="after_length" select="string-length($after)"/>

	<xsl:choose>
		<xsl:when test="$before_length &gt; 0 or starts-with($stringToSearchIn-CASE, $substringToHighlight-CASE)">
			<xsl:choose>
				<xsl:when test="$mode='search'">
					<xsl:call-template name="chaff">
						<xsl:with-param name="substring" select="substring($stringToSearchIn, 1, $before_length + string-length($substringToHighlight))"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="substring($stringToSearchIn, 1, $before_length + string-length($substringToHighlight))"/>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:choose>
				<xsl:when test="$mode='devmode'">
					<xsl:call-template name="devmode">
						<xsl:with-param name="substring" select="substring($stringToSearchIn, 1 + $before_length + string-length($substringToHighlight), $after_length)"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:when test="$mode='search'">
					<xsl:call-template name="wheat">
						<xsl:with-param name="substring" select="substring($stringToSearchIn, 1 + $before_length + string-length($substringToHighlight), $after_length)"/>
					</xsl:call-template>
				</xsl:when>
			</xsl:choose>
			<xsl:call-template name="HighlightPreg">
				<xsl:with-param name="stringToSearchIn" select="substring($stringToSearchIn, 1 + $before_length + string-length($substringToHighlight) + $after_length)"/>
				<xsl:with-param name="substringToHighlight" select="$substringToHighlight"/>
				<xsl:with-param name="mode" select="$mode"/><!--NOTE: sends $mode-->
			</xsl:call-template>
		</xsl:when>

		<xsl:otherwise>
			<xsl:choose>
				<xsl:when test="$mode='search'">
					<xsl:call-template name="chaff">
						<xsl:with-param name="substring" select="$stringToSearchIn"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$stringToSearchIn"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>


<xsl:template name="chaff">
	<xsl:param name="substring"/>
	<xsl:value-of select="$substring"/>
</xsl:template>

<xsl:template name="wheat">
	<xsl:param name="substring"/>
	<preg cat="{$substring}"><xsl:value-of select="$substring"/></preg>
</xsl:template>

<xsl:template name="devmode">
	<xsl:param name="substring"/>
	<span style="font-weight:bold;background-color:#EE0000;"><xsl:value-of select="$substring"/></span>
</xsl:template>


<!-- silences output of sitemap in content output -->
<xsl:template match="/sitemap"/>


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

<!-- same, but with mode=table -->
<xsl:template match="*" mode="table">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:if test="$devmode = 'Y'">
			<xsl:attribute name="devmode"><xsl:value-of select="name()"/></xsl:attribute>
			<xsl:attribute name="templatemode">table</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="node()"/>
	</xsl:copy>
</xsl:template>

<!-- same, but with mode=subsection -->
<xsl:template match="*" mode="subsection">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:if test="$devmode = 'Y'">
			<xsl:attribute name="devmode"><xsl:value-of select="name()"/></xsl:attribute>
			<xsl:attribute name="templatemode">subsection</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="node()"/>
	</xsl:copy>
</xsl:template>

<!-- same, but with mode=subsubsection -->
<xsl:template match="*" mode="subsubsection">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:if test="$devmode = 'Y'">
			<xsl:attribute name="devmode"><xsl:value-of select="name()"/></xsl:attribute>
			<xsl:attribute name="templatemode">subsubsection</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="node()"/>
	</xsl:copy>
</xsl:template>


</xsl:stylesheet>