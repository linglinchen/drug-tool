<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE QUESTION >
<!--<!DOCTYPE QUESTION PUBLIC "-//ES//DTD questions DTD version 1.0//EN//XML" "https://major-tool-development.s3.amazonaws.com/DTDs/questions_1_3.dtd">-->

<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
>
	
<!--
	converts existing, ExamReview, question XML into our DTD, normalizing elements into emphasis, for example

	2018-02-27 JWS	original
	2018-03-09 JWS	qnum is retained but qid is ignored; figure and audio booleans removed, they're in content; empty unit ignored; stratagem now has @type and strategy_categories is dumped; qtype=video removed; added tables
	2018-04-01 JWS	place stem and rationale content inside para, splitting on br, table and audio
	2018-05-21 JWS	fiddled with <math> and changed <component=figure> to use correct path; support for credit lines in <figure>; eliminated empty para from stem/rationale/math
	2019-03-28 JWS	updated DTD location and genericized params; qnum transformed to qid if qid not present and use generate-id instead of qnum comparison; hotspot; video qType=6 (multiple_response); $category_concatted
	2019-08-30 JWS	testlets; style=underline throws error message to check for tableness; testlet buttons converted to component; single character cyan-text no longer transformed to stratagem
	2019-09-27 JWS	entity_id assignment; testlet improvements; fixed li handling
	2019-11-22 JWS	entity_id assignment improved; added emphasis retention; merge adjacent mi/mspace text strings; appropriately split br when para is child of rationale (MathML)
	TODO: check that it is reliable to ignore qid and only retain qnum

	0. be sure your XSL transformation tool supports XSLT 3
	1. replace the source XML's DOCTYPE with the DOCTYPE from this XSLT. This removes the unfollowable reference to the old DTD and adds support for known entities
		a. life will also be easier if you combine all question XML files into a single file, adding the new root element, questions
	2. clean up source XML
		a. remove all <![CDATA[...]]> markup
		b. add new named entities to DOCTYPE to be replaced with characters (eg &ndash; is en dash: –); decimal entities do not require this
		c. fix well-formedness errors
		d. normalize stratagems in categories.xml
	3. transform the source XML against this XSLT
		a. if source does not have chapters defined, but should, look at rule for questions to transform into chapters; there are multiple OPTIONs for creating chapters, see below
		b. note setting of params:
			- devmode; enable to catch ignorable errors, check for br that might be accidentally deleted or should be converted into double br to split into para instead
			- categories file
			- ISBN
			- UUID values
			- existing_entityid_file if already assigned
			- (optional) category_concatted and category_concator
			- video_location
		c. note the questions template below sorts questions into chapters by qnum and will need to be updated to fit your dataset
		d. verify output does not contain ERROR text/attributes, especially when devmode=Y
	4. manually add xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd" to each mml:math node for METIS
	5. save transformed XML in a batch directory following this naming convention letter_a-z_4e.xml (lowercase and edition included)
	6. ZIP batch directory and upload to converted_XML bucket in project's bucket on S3
-->

<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD dictionary DTD version 1.0//EN//XML" doctype-system="https://major-tool-development.s3.amazonaws.com/DTDs/questions_1_3.dtd"
 media-type="text/html"/>
<!--https://major-tool-development.s3.amazonaws.com/DTDs/questions_1_2.dtd-->
<xsl:strip-space elements="question"/>
<xsl:preserve-space elements="br category option"/>

<xsl:param name="devmode" select="'N'"/>	<!-- set to Y to: 1) output error messages regarding underlines signaling tables -->
<!-- used to calculate the UUIDs, use the same values for each transformation of a single title to ensure the same values are calculated each time; recommend changing this for new titles -->
	<xsl:param name="uuid_multiplier" select="57003"/>
	<xsl:param name="uuid_increment" select="69779"/>
	<xsl:param name="uuid_divisor" select="51133"/>

<xsl:param name="existing_entityid_file" select="'NONE'"/> <!-- the location of the file containing the entity_ids already assigned in METIS; set to NONE if they have not been assigned -->

<xsl:variable name="entityids" select="document($existing_entityid_file)" as="document-node()"/>

<!-- unique identifier for previously assigned entity_ids -->
<xsl:key name="metisID" match="//atom" use="@title"/>

<!-- find assigned entity_id in $entityids -->
<xsl:template name="get_entity_id">
	<xsl:if test="$existing_entityid_file != 'NONE'">
		<xsl:variable name="temp" select="parent::*/qid | qid | parent::*/qnum | qnum"/>
		<xsl:value-of select="$entityids/key('metisID',$temp)/@entity_id"/>
	</xsl:if>
</xsl:template>



<xsl:param name="isbn" select="/questions/@isbn"/> <!-- the source book's ISBN -->
<xsl:param name="category_xml_file" select="'c:\temp\delete\Silvestri_comprehensivePN8e\questions\categories.xml'"/> <!-- the location of the file containing the categories used on Evolve -->


<!-- this file is used by Evolve to build the question selector, it covers several different cataloging areas -->
<xsl:variable name="categories" select="document($category_xml_file)" as="document-node()"/>

<!-- allows translating numeric IDs back into text strings for us to store in atom XML -->
<xsl:key name="category_num" match="*[not(child::*)]" use="concat(ancestor::category/@node, '::', @num)"/>
<!-- used to concat ancestors to self in output for selected categories -->
<xsl:param name="category_concatted"  select="'|content_area|health_codes|'"/>
<xsl:param name="category_concator"  select="': '"/>

<!-- allows translating text back into text strings for us to store in atom XML; @alternate is used to normalize alternate text entries into a single cataloging name (eg, both plural and singular) -->
<xsl:key name="category_name" match="*[not(child::*)]" use="concat(ancestor::category/@node, '::', lower-case(@name))"/>
<xsl:key name="category_name" match="*[not(child::*)]" use="concat(ancestor::category/@node, '::', lower-case(@alternate))"/>

<!-- sometimes videos are only used in stem, other times they are also in rationales; so far it's been binary by title -->
<xsl:param name="video_location" select="'stem rationale'"/>


<!-- used both as a prefix and to seed the calculation of UUIDs, this counts the number of characters in a document and will result in repeatable UUID generation on a single document yet be fairly unique if not used frequently
	NOTE: outside devmode, if the document text changes length by even one character, UUIDs will not be consistent across transformations so fix characters and content *before* relying on the UUIDs -->
<xsl:variable name="entity_id_base">
	<xsl:choose>
		<!-- CAUTION: for speed and simplicity, devmode will use an arbitrary number to force UUIDs to be the same while during testing and modifying XML, final output UUIDs will be different than during devmode -->
		<xsl:when test="$devmode='Y'">5993212</xsl:when>
		<xsl:otherwise><xsl:value-of select="string-length(*)"/></xsl:otherwise>
	</xsl:choose>
</xsl:variable>

<!-- seeds a document tree with UUIDs paired to unique IDs for each atomic element, which is then used in $uuid_key and $uuid_uniquenode -->
<xsl:variable name="uuid_tree">
	<uuids>
		<xsl:call-template name="generateUUID">
			<xsl:with-param name="seed" select="$entity_id_base"/>
			<xsl:with-param name="atoms" select="/questions/question | /questions/chapter/question"/>
			<xsl:with-param name="counter" select="1"/>
		</xsl:call-template>
	</uuids>
</xsl:variable>

<!-- keys used to improve the efficiency of applying UUIDs to atoms -->
<!-- NOTE: if document has a default namespace, this will have to use it -->
<xsl:key name="uuid_key" match="$uuid_tree//atom" use="@id"/>
<xsl:key name="uuid_uniquenode" match="$uuid_tree//atom" use="@uniquenode"/>


<!-- populates $uuid_tree with a pseudo-UUID from a seed, based on the Linear Congruential Generator: Xn+1 = (aXn + c) mod c -->
<!-- $atoms are the set of elements in source needing UUIDs once transformed into atoms -->
<xsl:template name="generateUUID">
	<!-- This seed begins as $entity_id_base, but once generateUUID is recursing it becomes the previous atom's UUID -->
	<xsl:param name="seed"/>
	<!-- Select the unique nodes in source that will require a UUID upon transformation -->
	<xsl:param name="atoms"/>
	<xsl:param name="counter"/><!-- USED TO LIMIT EXPENSE OF KEY CALCULATION when $devmode=Y -->
	
	<!--($a * $seed + $c) mod $m-->
	<xsl:variable name="uuid" select="($uuid_multiplier * $seed + $uuid_increment) mod $uuid_divisor"/>
	
	<!-- first atom in $atoms -->
	<xsl:element name="atom">
		<xsl:attribute name="uuid" select="format-number($uuid, '999999')"/>
		<xsl:attribute name="genid" select="generate-id($atoms[position()=1])"/>
		<xsl:attribute name="uniquenode" select="$atoms[position()=1]/qid | $atoms[position()=1]/qnum"/>	<!-- a distinct per atom, omnipresent node to test pairing source atoms with generated IDs -->
	</xsl:element>
	<!-- recurses through all of the $atoms so the current seed can be used to calculate the next UUID -->
	<!-- $counter combined with $devmode=Y will result in only calculating 10 UUIDs -->
	<xsl:if test="($devmode != 'Y' or $counter &lt; 10) and count($atoms) &gt; 1">
	<!--<xsl:if test="count($atoms) &gt; 1">-->
		<xsl:call-template name="generateUUID">
			<xsl:with-param name="seed" select="$uuid"/>
			<xsl:with-param name="atoms" select="$atoms[not(position()=1)]"/>
			<xsl:with-param name="counter" select="$counter + 1"/><!-- KEY creation limiter -->
		</xsl:call-template>
	</xsl:if>
	
</xsl:template>






<!-- OPTION 1: when chapters aren't defined in source XML, this template does it for you based on qnums -->
<xsl:template match="questions" priority="0">
	<xsl:comment>Manually add xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd" to each mml:math node for METIS</xsl:comment><xsl:text>&#xa;</xsl:text>

	<questions>
		<xsl:attribute name="isbn" select="$isbn"/>

		<!-- Repeat first chapter block for each chapter and use Evolve chapter for remainder -->
		<chapter id="c_REPLACE_ME__" number="__">
			<label>Chapter __</label>
			<title>TITLE_OF_CHAPTER_IN_TITLE_CASE</title>
			<xsl:apply-templates select="question[qnum&gt;=__BEGIN__ and qnum&lt;=__END__]">
				<xsl:sort select="qnum/text()" data-type="number"/>
			</xsl:apply-templates>
		</chapter>
		
		<!-- etc -->
		
		<chapter id="c_REPLACE_ME__" number="evolve">
			<label>Evolve</label>
			<title>Evolve-Only Questions</title>
			<xsl:apply-templates select="question[qnum&gt;=__BEGIN__]">
				<xsl:sort select="qnum/text()" data-type="number"/>
			</xsl:apply-templates>
		</chapter>
	</questions>
</xsl:template>

<!-- OPTION 2: chapters are declared using file node: ch__; all others are "Evolve" -->
<xsl:template match="questions[starts-with(question[1]/file, 'ch')]" priority="2">
	<xsl:comment>Manually add xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd" to each mml:math node for METIS</xsl:comment><xsl:text>&#xa;</xsl:text>

	<questions>
		<xsl:attribute name="isbn" select="$isbn"/>
		
		<!-- FOR TESTING: it's helpful to limit the selected question nodes with [position() &lt; ___], especially when trying to track down source XML errors that break output -->

		 <xsl:for-each-group select="question" group-by="file[starts-with(., 'ch')]">
			<xsl:variable name="chapter" select="number(substring-after(current-grouping-key(), 'ch'))"/>
			<chapter id="c_REPLACE_ME__" number="{$chapter}">
				<label>Chapter <xsl:value-of select="$chapter"/></label>
				<title>Chapter <xsl:value-of select="$chapter"/></title>
				<xsl:apply-templates select="current-group()">
					<xsl:sort select="qnum/text()" data-type="number"/>
				</xsl:apply-templates>
			</chapter>
		 </xsl:for-each-group>
		 
		 <xsl:apply-templates select="." mode="testlets"/>
		
		<chapter id="c_REPLACE_ME__" number="evolve">
			<label>Evolve</label>
			<title>Evolve-Only Questions</title>
			<xsl:apply-templates select="question[not(starts-with(file, 'ch'))][not(question)]">
				<xsl:sort select="qnum/text()" data-type="number"/>
			</xsl:apply-templates>
		</chapter>
	</questions>
</xsl:template>

<!-- OPTION 3: when chapters are defined in source XML, this gets used -->
<xsl:template match="questions[chapter]" priority="3">
	<xsl:comment>Manually add xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd" to each mml:math node for METIS</xsl:comment><xsl:text>&#xa;</xsl:text>

	<questions>
		<xsl:attribute name="isbn" select="$isbn"/>

		<!-- consumes existing chapters and reorders everything numerically -->
		<xsl:apply-templates select="chapter">
			<xsl:sort select="qnum/text() | qid/text()" data-type="number"/>
		</xsl:apply-templates>	
	</questions>
</xsl:template>

<!-- OPTION 4: when processing testlets in a segregated file -->
<xsl:template match="questions[question/question]" priority="1">
	<xsl:comment>Manually add xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd" to each mml:math node for METIS</xsl:comment><xsl:text>&#xa;</xsl:text>

	<questions>
		<xsl:attribute name="isbn" select="$isbn"/>

		<xsl:apply-templates select="." mode="testlets"/>

	</questions>
</xsl:template>

<!-- no testlets -->
<xsl:template match="questions" mode="testlets"/>

<!-- makes the testlets chapter -->
<xsl:template match="questions[question/question]" mode="testlets">
	<chapter id="c_REPLACE_ME__" number="testlets">
		<label>Testlets</label>
		<title>Testlets</title>
		<xsl:apply-templates select="question[question]"/>
	</chapter>
</xsl:template>


<!-- add @id, @status -->
<xsl:template match="question">
	<question status="active">
		<xsl:attribute name="id">
			<xsl:call-template name="set_atom_id"/>
		</xsl:attribute>
		<xsl:apply-templates/>
	</question>
</xsl:template>

<!-- this is a case study/testlet whose stem is *repeated* in the sub-questions -->
<xsl:template match="question[question][not(stem)]">
	<question status="active">
		<xsl:attribute name="id">
			<xsl:call-template name="set_atom_id"/>
		</xsl:attribute>
		<xsl:apply-templates select="node()[local-name() != 'question']"/>
		<xsl:apply-templates select="question[1]/stem" mode="casestudy"/>
		<xsl:apply-templates select="question"/>
	</question>
</xsl:template>

<!-- TODO: see questions template for sorting by file; exclude from output it is reserved for component -->
<xsl:template match="file[parent::question]"/>

<!-- conditionally ignore, tends to be identical to qnum and we have entity_id now; will only use if qnum not present -->
<xsl:template match="qid">
	<qnum>
		<xsl:apply-templates/>
	</qnum>
</xsl:template>
<xsl:template match="qid[preceding-sibling::qnum or following-sibling::qnum]"/>

<!-- Evolve=qType -->
<xsl:template match="qType">
	<qtype>
		<xsl:choose>
			<!-- 5=video, 7=hotspot are converted to best_answer for Silvestri_strategies5e, Silvestri_QARN7e -->
			<xsl:when test=".='1' or .='5' or .='7'"><xsl:attribute name="type">best_answer</xsl:attribute></xsl:when>
			<!-- 6=video is converted to multiple for Silvestri_QARN7e -->
			<xsl:when test=".='2' or .='6'"><xsl:attribute name="type">multiple</xsl:attribute></xsl:when>
			<xsl:when test=".='3'"><xsl:attribute name="type">fill-in-the-blank</xsl:attribute></xsl:when>
			<xsl:when test=".='4'"><xsl:attribute name="type">ordered_response</xsl:attribute></xsl:when>
			<!-- TODO: "Video Response" is not really a question type, they seem to always be best_answer but would require a change to data -->
			<!--<xsl:when test=".='5'"><xsl:attribute name="type">video</xsl:attribute></xsl:when>-->
			<!--<xsl:when test=".='6'"><xsl:attribute name="type">video? Silvestri_QARN7e/qid=5443</xsl:attribute></xsl:when>-->
			<!--<xsl:when test=".='7'"><xsl:attribute name="type">hotspot</xsl:attribute></xsl:when>-->
			<xsl:when test=".='8'"><xsl:attribute name="type">testlet</xsl:attribute></xsl:when>
			<!-- catches errors in source XML -->
			<xsl:otherwise>
				<xsl:attribute name="ERROR" select="'Unrecognized or missing qType'"/>
			</xsl:otherwise>
		</xsl:choose>
	</qtype>
</xsl:template>


<!-- subject categories, comma separated numeric values, may be empty (Evolve XML output renames to category1..7) -->
<!-- Client Needs -->
<xsl:template match="category1">
	<client_needs>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'client_needs'"/>
		</xsl:call-template>
	</client_needs>
</xsl:template>
<!--Level of Cognitive Ability -->
<xsl:template match="category2">
	<cognitive_ability>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'cognitive_ability'"/>
		</xsl:call-template>	
	</cognitive_ability>
</xsl:template>
<!-- Content Area -->
<xsl:template match="category3">
	<content_area>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'content_area'"/>
		</xsl:call-template>	
	</content_area>
</xsl:template>
<!-- Health Codes -->
<xsl:template match="category4">
	<health_codes>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'health_codes'"/>
		</xsl:call-template>	
	</health_codes>
</xsl:template>
<!-- Integrated Process -->
<xsl:template match="category5">
	<integrated_process>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'integrated_process'"/>
		</xsl:call-template>	
	</integrated_process>
</xsl:template>
<!-- Priority Concepts, should be alphabetical by tag -->
<xsl:template match="category6">
	<priority_concepts>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'priority_concepts'"/>
		</xsl:call-template>	
	</priority_concepts>
</xsl:template>
<!-- Strategy is redundant -->
<xsl:template match="category7"/>
<!--<xsl:template match="category7">
	<strategy_categories>
		<xsl:call-template name="category_by_num">
			<xsl:with-param name="category" select="'strategy_categories'"/>
		</xsl:call-template>	
	</strategy_categories>
</xsl:template>-->



<!-- places figures/videos/hotspots into stem based on presence of figure boolean; converts stem2 to stem (case study/testlet) -->
<xsl:template match="stem | stem2" mode="#all">
	<stem>
		<para>
			<!-- usually @id is copied, this enforces required presence and uniqueness -->
			<xsl:call-template name="id_generator">
				<xsl:with-param name="nodename" select="'para'"/>
			</xsl:call-template>
			<xsl:apply-templates/>
			<xsl:apply-templates select="parent::*/figure[text()='1'] | button" mode="stem"/>
			<!-- video -->
			<xsl:apply-templates select="parent::*/qType[.='5' or .='6'][contains($video_location, 'stem')]" mode="stem">
				<xsl:with-param name="context" select="'stem'"/>
			</xsl:apply-templates>
			<!-- hotspot -->
			<xsl:apply-templates select="parent::*/option1/div[@class='hotspot-image']/img" mode="stem"/>
		</para>
	</stem>
</xsl:template>

<!-- split into para, separating on table, audio or br in pairs -->
<xsl:template match="stem[table|audio|br] | stem2[table|audio|br]" mode="#all">
	<stem>
		<!-- the double br rule is complicated -->
         <xsl:for-each-group select="text()|*" group-starting-with="table|audio|br[not(local-name(preceding-sibling::node()[1]) = 'br')][local-name(following-sibling::node()[1]) = 'br'][not(local-name(following-sibling::node()[2]) = 'audio' or local-name(following-sibling::node()[2]) = 'button')][string-length(following-sibling::node()[2][normalize-space()]) > 0 or following-sibling::node()[3]]" >
			 <para>
				<!-- usually @id is copied, this enforces required presence and uniqueness -->
				<xsl:call-template name="id_generator">
					<xsl:with-param name="nodename" select="'para'"/>
				</xsl:call-template>
				  <xsl:apply-templates select="current-group()"/>
			 </para>
         </xsl:for-each-group>
		<xsl:apply-templates select="parent::*/figure[text()='1'] | button" mode="stem"/>
		<!-- video -->		
		<xsl:apply-templates select="parent::*/qType[.='5' or .='6'][contains($video_location, 'stem')]" mode="stem">
			<xsl:with-param name="context" select="'stem'"/>
		</xsl:apply-templates>
		<!-- hotspot -->
		<xsl:apply-templates select="parent::*/option1/div[@class='hotspot-image']/img" mode="stem"/>
	</stem>
</xsl:template>

<!-- ignore the case study/testlet stem that is repeated in the children, it is moved to parent with mode=casestudy -->
<xsl:template match="stem[parent::question/parent::question[not(stem)]]" mode="#unnamed"/>


<!-- eliminates extraneous br from stem, rationale and math -->
<xsl:template match="br[parent::stem or parent::stem2] | br[parent::rationale] | br[parent::span[@class='formula']][local-name(preceding-sibling::node()[1]) = 'br' or local-name(following-sibling::node()[1]) = 'br']"/>

<xsl:template match="br[parent::stem or parent::stem2][$devmode='Y'] | br[parent::rationale][$devmode='Y'] | br[parent::span[@class='formula']][local-name(preceding-sibling::node()[1]) = 'br' or local-name(following-sibling::node()[1]) = 'br'][$devmode='Y']">
	<br>
		<xsl:attribute name="ERROR" select="'This br will be deleted, check if it should be doubled for splitting'"/>
	</br>
</xsl:template>


<xsl:template match="rationale">
	<rationale>
		<para>
			<!-- usually @id is copied, this enforces required presence and uniqueness -->
			<xsl:call-template name="id_generator">
				<xsl:with-param name="nodename" select="'para'"/>
			</xsl:call-template>
			<xsl:apply-templates/>
			<!-- video -->
			<xsl:apply-templates select="parent::*/qType[.='5' or .='6'][contains($video_location, 'rationale')]" mode="stem">
				<xsl:with-param name="context" select="'rationale'"/>
			</xsl:apply-templates>
		</para>
	</rationale>
</xsl:template>

<!-- split into para, separating on para, table, audio or br in pairs; MathML para are often preceded by b node so we include in for-each-group and not in match; see also MathML para rule below -->
<xsl:template match="rationale[table|audio|br]">
	<rationale>
		<!-- group on para to prevent nesting inside the generated para of the previous sibling -->
         <xsl:for-each-group select="text()|*" group-starting-with="para|table|audio|br[not(local-name(preceding-sibling::node()[1]) = 'br')][local-name(following-sibling::node()[1]) = 'br'][not(local-name(following-sibling::node()[2]) = 'audio')][string-length(following-sibling::node()[2][normalize-space()]) > 0 or following-sibling::node()[3]]">
			 <para>
				<!-- usually @id is copied, this enforces required presence and uniqueness -->
				<xsl:call-template name="id_generator">
					<xsl:with-param name="nodename" select="'para'"/>
				</xsl:call-template>
				<xsl:apply-templates select="current-group()"/>
			 </para>
         </xsl:for-each-group>	
			<!-- video -->
			<xsl:apply-templates select="parent::*/qType[.='5' or .='6'][contains($video_location, 'rationale')]" mode="stem">
				<xsl:with-param name="context" select="'rationale'"/>
			</xsl:apply-templates>
	</rationale>
</xsl:template>

<!-- ignore; these are now marked on option nodes for qTypes 1,2,4,5,6,7 -->
<xsl:template match="correctAnswer"/>

<!-- Fill-in-the-blank; contains text of correct answer; Evolve=correctAnswer -->
<xsl:template match="correctAnswer[parent::question/qType[.='3']]">
	<correct_answer>
		<xsl:apply-templates/>
	</correct_answer>
</xsl:template>

<!-- ignore when empty; should only be present for fill-in-the-blank -->
<xsl:template match="unit[not(text())]"/>

<!-- the question's answers and distractors, 8 required for Evolve (node names are sequentially numbered in Evolve XML output) -->
<xsl:template match="option1|option2|option3|option4|option5|option6|option7|option8">
	<xsl:variable name="answer" select="substring(local-name(), 7)"/>
	<option>
		<!--1=Best Answer, 2=Multiple Response, 3=Fill-In-The-Blank, 4=Priority Order, 5=Video (best), 6=Video (multiple), 7=Hotspot (single answer)-->
		<xsl:choose>
			<xsl:when test="parent::question/qType[.='1' or .='2' or .='5' or .='6' or .='7']">
				<xsl:if test="parent::question/correctAnswer[contains(., $answer)]">
					<xsl:attribute name="correct" select="'correct'"/>
				</xsl:if>
			</xsl:when>
			<!-- Ordered Response; this captures the priority -->
			<xsl:when test="parent::question/qType[.='4'] and parent::question/correctAnswer[contains(., $answer)]">
				<xsl:call-template name="ordered_response_answers">
					<xsl:with-param name="answer" select="$answer"/>
					<xsl:with-param name="correctAnswer" select="parent::question/correctAnswer"/>
					<xsl:with-param name="order" select="1"/>
				</xsl:call-template>
			</xsl:when>
			<!-- catches errors in source XML -->
			<xsl:otherwise>
				<xsl:attribute name="ERROR" select="'Unrecognized or missing qType'"/>
			</xsl:otherwise>

		</xsl:choose>
		<xsl:apply-templates/>
	</option>
</xsl:template>

<!-- ignore empty answers/distractors -->
<xsl:template match="option1[not(text())]|option2[not(text())]|option3[not(text())]|option4[not(text())]|option5[not(text())]|option6[not(text())]|option7[not(text())]|option8[not(text())]"/>

<xsl:template match="b|strong | emphasis[@style='bold']">
	<emphasis style="bold"><xsl:apply-templates/></emphasis>

</xsl:template>

<xsl:template match="i|em | emphasis[@style='italic']">
	<emphasis style="italic"><xsl:apply-templates/></emphasis>

</xsl:template>

<!-- AM and PM -->
<xsl:template match="span[@style='font-variant: small-caps;'] | emphasis[@style='smallcaps']">
	<emphasis style="smallcaps"><xsl:apply-templates/></emphasis>
</xsl:template>

<xsl:template match="span[@style='text-decoration:underline;'] | emphasis[@style='underline']">
	<emphasis style="underline">
		<xsl:if test="$devmode='Y'">
			<xsl:attribute name="ERROR" select="'check if this is a table'"/>
		</xsl:if>
		<xsl:apply-templates/>
	</emphasis>
</xsl:template>

<!-- a single strategy highlighted in strategy section -->
<xsl:template match="span[@class='cyan-text']">
	<stratagem>
		<xsl:attribute name="type">
			<xsl:call-template name="category_by_name">
				<xsl:with-param name="category" select="'strategy_categories'"/>
			</xsl:call-template>	
		</xsl:attribute>
		<xsl:apply-templates/>
	</stratagem>
</xsl:template>

<!-- these contain punctuation incorrectly sharing style of preceding text and should not be marked as stratagem -->
<xsl:template match="span[@class='cyan-text'][string-length()=1]">
	<xsl:apply-templates/>
</xsl:template>

<!-- it is wrong for stratagem to be in rationales (Silvestri_QARN7e qid=233) -->
<xsl:template match="span[@class='cyan-text'][parent::rationale]">
	<xsl:apply-templates/>
</xsl:template>

<!-- a single subject highlighted in a content review section -->
<xsl:template match="span[@class='magenta-text']">
	<subject><xsl:apply-templates/></subject>
</xsl:template>

<!-- convert formula nodes to namespaceless MathML; split on triples of br; they will still need work to get numerators/denominators right because source splits formulas with fractions onto three instead of two lines -->
<xsl:template match="span[@class='formula']">
	<xsl:for-each-group select="text()|*" group-starting-with="br[local-name(preceding-sibling::node()[1]) = 'br'][local-name(following-sibling::node()[1]) = 'br']" >
	<math>
		<mfrac>
			<mi>
				<xsl:apply-templates select="current-group()"/>
			</mi>
		</mfrac>
	</math>
	</xsl:for-each-group>	
</xsl:template>


<!-- BEGIN: merging single-character mi/mspace nodes into longer mi nodes -->
<!-- these just result in extraneous newlines when merging mi -->
<xsl:template match="text()[parent::mml:mrow]"/>

<!-- merging adjacent mi, first node; mfrac bevelled are character-level strings -->
<xsl:template match="mml:mi[not(preceding-sibling::*[1]/local-name()='mi')][not(preceding-sibling::*[1][local-name()='mspace'][preceding-sibling::*[1]/local-name()='mi'])][not(parent::mml:mfrac[@bevelled='true'])]">
	<mml:mi>
		<xsl:apply-templates/>
		<xsl:apply-templates select="following-sibling::*[1][local-name()='mi'] | following-sibling::*[1][local-name()='mspace'][following-sibling::*[1]/local-name()='mi']" mode="merge_mi"/>
	</mml:mi>
</xsl:template>

<!-- skip these, they're caught in merge_mi -->
<xsl:template match="mml:mi[preceding-sibling::*[1]/local-name()='mi'][not(parent::mml:mfrac[@bevelled='true'])] | mml:mi[preceding-sibling::*[1][local-name()='mspace'][preceding-sibling::*[1]/local-name()='mi']][not(parent::mml:mfrac[@bevelled='true'])]"/>

<!-- merge these -->
<xsl:template match="mml:mi[preceding-sibling::*[1]/local-name()='mi'] | mml:mi[preceding-sibling::*[1][local-name()='mspace'][preceding-sibling::*[1]/local-name()='mi']]" mode="merge_mi">
	<xsl:apply-templates/>
	<xsl:apply-templates select="following-sibling::*[1][local-name()='mi'] | following-sibling::*[1][local-name()='mspace'][following-sibling::*[1]/local-name()='mi']" mode="merge_mi"/>
</xsl:template>

<!-- skip these, they're caught in merge_mi -->
<xsl:template match="mml:mspace[preceding-sibling::*[1]/local-name()='mi'][following-sibling::*[1]/local-name()='mi']"/>

<!-- merge these as a space into the larger string of mi text -->
<xsl:template match="mml:mspace[preceding-sibling::*[1]/local-name()='mi'][following-sibling::*[1]/local-name()='mi']" mode="merge_mi">
	<xsl:text> </xsl:text>
	<xsl:apply-templates select="following-sibling::*[1][local-name()='mi'] | following-sibling::*[1][local-name()='mspace'][following-sibling::*[1]/local-name()='mi']" mode="merge_mi"/>
</xsl:template>
<!-- END: merging single-character mi/mspace nodes into longer mi nodes -->



<xsl:template match="sub | emphasis[@style='inf']">
	<emphasis style="inf"><xsl:apply-templates/></emphasis>

</xsl:template>

<xsl:template match="sup | emphasis[@style='sup']">
	<emphasis style="sup"><xsl:apply-templates/></emphasis>

</xsl:template>

<xsl:template match="a">
	<xref refid="{@href}"><xsl:apply-templates/></xref>
</xsl:template>

<!-- ignore this, it is a boolean indicating the question includes a figure, so it's redundant because those are placed in component -->
<xsl:template match="figure"/>

<!-- ignore hotspot-image, it will be converted to component and placed inside stem;
	hotspot-highlight and hotspot-checkmark are used only by application and are theoretically product-specific -->
<xsl:template match="div[@class='hotspot-image' or @class='hotspot-highlight' or @class='hotspot-checkmark']"/>

<!-- dump the buttons used in case study/testlet and upgrade to component with mode=stem (probably would elsewhere, but those seem not to exist) -->
<xsl:template match="button[count(ancestor::question) > 1]"/>

<xsl:template match="figure[text()='1'] | img[parent::div[@class='hotspot-image']] | button" mode="stem">
	<xsl:variable name="qnum">
		<xsl:call-template name="qnum"/>
	</xsl:variable>

	<component type="figure">
		<xsl:attribute name="id" select="concat('f', $qnum, '_', (count(preceding::figure[.='1'][generate-id(ancestor::question[1]) = generate-id(current()/ancestor::question[1])]) + 1))"/>
		<!-- hotspot uses image map -->
		<xsl:if test="following-sibling::map">
			<xsl:attribute name="usemap" select="concat('#hsm', generate-id(ancestor::question))"/>
		</xsl:if>
		<!--<file src="{concat('figures/', ancestor::question/qnum, '.jpg')}"></file>-->
		<file src="{$qnum}"></file>
		<xsl:apply-templates select="credit | following-sibling::map"/>
	</component>
</xsl:template>

<xsl:template match="credit">
	<credit>
		<xsl:apply-templates/>
	</credit>
</xsl:template>

<!-- hotspot image map; generate unique id/name for use by usemap to avoid collisions -->
<xsl:template match="map">
	<map>
		<xsl:attribute name="id" select="concat('hsm', generate-id(ancestor::question))"/>
		<xsl:attribute name="name" select="concat('hsm', generate-id(ancestor::question))"/>

		<xsl:apply-templates/>
	</map>
</xsl:template>

<!-- ignore this, it is a boolean indicating the audio element is in use, so it's redundant -->
<xsl:template match="audio"/>

<!-- convert HTML audio elements to components; there is also a redundant audio boolean element which is removed -->
<xsl:template match="audio[@src]">
	<xsl:variable name="qnum">
		<xsl:call-template name="qnum"/>
	</xsl:variable>

	<component type="audio">
		<xsl:attribute name="id" select="concat('a', $qnum, '_', (count(preceding::audio[@src][generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1))"/>
		<file src="{@src}"></file>
	</component>

</xsl:template>

<!-- inject a video component where none was; originally created a para for METIS, but that could result in nested para
	requiring manual repair and is not required by DTD, plus it can be fixed via in.xslt -->
<xsl:template match="qType[.='5' or .='6']" mode="stem">
	<xsl:param name="context"/>
	<xsl:variable name="suffix">
		<xsl:choose>
			<xsl:when test="$context='rationale'">r</xsl:when>
			<xsl:when test="$context='stem'">q</xsl:when>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="qnum">
		<xsl:call-template name="qnum"/>
	</xsl:variable>	
	
		<component type="video">
			<xsl:attribute name="id" select="concat('v', $suffix, $qnum, '_', (count(preceding::qType[.='5' or .='6']) + 1))"/>
			<file src="{concat('videos/', $qnum, '_', $suffix, '.mp4')}"></file>
		</component>

</xsl:template>


<!-- table fixers -->
<xsl:template match="table">
	<!-- qnum test removed -->

	<table>
		<xsl:copy-of select="@*"/>
		<xsl:if test="not(@id)">
			<xsl:attribute name="id" select="concat('t', (count(preceding::table[generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1))"/>
		</xsl:if>
		<xsl:apply-templates/>
	</table>

</xsl:template>

<xsl:template match="tgroup">
	<!-- qnum test removed -->

	<tgroup>
		<xsl:copy-of select="@*"/>
		<xsl:if test="not(@id)">
			<xsl:attribute name="id" select="concat('tg', (count(preceding::table[generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1), '_', count(preceding-sibling::tgroup) + 1)"/>
		</xsl:if>
		<xsl:call-template name="colspec">
			<xsl:with-param name="count" select="1"/>
			<xsl:with-param name="limit" select="@cols"/>
		</xsl:call-template>
		<xsl:apply-templates/>
	</tgroup>

</xsl:template>

<xsl:template name="colspec">
	<xsl:param name="count"/>
	<xsl:param name="limit"/>
	
	<xsl:if test="$count &lt;= $limit">
		<colspec>
			<xsl:attribute name="colname" select="concat('col', $count)"/>
			<xsl:attribute name="colnum" select="$count"/>
		</colspec>
		
		<xsl:call-template name="colspec">
			<xsl:with-param name="count" select="$count + 1"/>
			<xsl:with-param name="limit" select="$limit"/>
		</xsl:call-template>
	</xsl:if>

</xsl:template>

<xsl:template match="row">
	<!-- qnum test removed -->

	<row>
		<xsl:copy-of select="@*"/>
		<xsl:if test="not(@id)">
			<xsl:attribute name="id" select="concat('tr', (count(preceding::table[generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1), '_', count(preceding::row[generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1)"/>
		</xsl:if>
		<xsl:apply-templates/>
	</row>

</xsl:template>


<xsl:template match="list | ul">
	<!-- qnum test removed -->

	<list>
		<xsl:copy-of select="@*"/>
		<xsl:if test="not(@id)">
			<xsl:attribute name="id" select="concat('l', (count(preceding::list[generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1))"/>
		</xsl:if>
		
		<xsl:apply-templates/>
	</list>

</xsl:template>

<xsl:template match="li">
	 <item level="1">
		<label>•</label>
		<para>
			<!-- usually @id is copied, this enforces required presence and uniqueness -->
			<xsl:call-template name="id_generator">
				<xsl:with-param name="nodename" select="'para'"/>
			</xsl:call-template>

			<xsl:apply-templates/>
		</para>
	 </item>
</xsl:template>

<!-- originally this preserved para that had probably been added manually; it also preserves para inserted when folding TnQ's MathML back in to source -->
<xsl:template match="para">
	<!-- qnum test removed -->

	<para>
		<xsl:copy-of select="@*"/>
		<xsl:if test="not(@id)">
			<xsl:attribute name="id" select="concat('p', (count(preceding::para[generate-id(ancestor::question) = generate-id(current()/ancestor::question)]) + 1))"/>
		</xsl:if>
		
		<xsl:apply-templates/>
	</para>

</xsl:template>

<!-- added MathML are inside para and will have a para applied by the parent's for-each-group rule, this avoids nesting inside that para -->
<xsl:template match="para[parent::rationale][child::mml:math]">
	<xsl:apply-templates/>

</xsl:template>


<!-- sometimes source XML has qnum, sometimes only qid; qnum is preferred -->
<xsl:template name="qnum">
	<!-- case study/testlet images need to have 0 prefix added because they're not on the qid -->
	<xsl:if test="count(current()/ancestor::question) > 1">
		<xsl:text>0</xsl:text>
	</xsl:if>
	<xsl:choose>
		<xsl:when test="current()/ancestor::question[1]/qnum">
			<xsl:value-of select="current()/ancestor::question[1]/qnum/text()"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="current()/ancestor::question[1]/qid/text()"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!-- expands each numeric category value into the text label found in $categories; joins ancestor @name for those
	categories specified in $category_concatted using $category_concator (eg, sub: subsub: subsubsub) -->
<xsl:template name="category_by_num">
	<xsl:param name="category"/>
	
	<xsl:for-each select="tokenize(., ',')">
		<xsl:variable name="temp" select="."/>
		<entry>
			<xsl:value-of select="$categories/key('category_num', concat($category, '::', $temp))/concat(
				string-join(ancestor-or-self::*[not(self::category)][contains($category_concatted, concat('|', $category, '|'))]/@name, $category_concator),
				@name[not(ancestor::category/@name[contains($category_concatted, concat('|', $category, '|'))])]
			)"/>
		</entry>
	</xsl:for-each>
</xsl:template>

<!-- expands each text category value into the text label found in $categories -->
<xsl:template name="category_by_name">
	<xsl:param name="category"/>

	<xsl:variable name="temp" select="lower-case(.)"/>
	<xsl:variable name="category_name" select="distinct-values($categories/key('category_name', concat($category, '::', $temp))/@name)"/>
	<xsl:choose>
		<xsl:when test="boolean($category_name)">
			<xsl:value-of select="$category_name"/>
		</xsl:when>
		
		<!-- use these to build @alternate values in $categories XML to match against; it's also OK to fix source XML when the alternate plain text needn't be retained as-is -->
		<xsl:otherwise>
			<xsl:text>ERROR: category was not found</xsl:text>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>


<!-- correctAnswer node is source contains comma-separated ordered list; this converts that into option/@order; @order need not be distinct or sequential, but every option must have a value -->
<xsl:template name="ordered_response_answers">
	<xsl:param name="answer"/>
	<xsl:param name="correctAnswer"/>
	<xsl:param name="order"/>

	<xsl:choose>
		<xsl:when test="substring-before($correctAnswer, ',') = $answer or $correctAnswer = $answer">
			<xsl:attribute name="order" select="$order"/>
		</xsl:when>
		<xsl:when test="contains($correctAnswer, ',')">
			<xsl:call-template name="ordered_response_answers">
				<xsl:with-param name="answer" select="$answer"/>
				<xsl:with-param name="correctAnswer" select="substring-after($correctAnswer, ',')"/>
				<xsl:with-param name="order" select="$order + 1"/>
			</xsl:call-template>
		</xsl:when>
		<!-- catches errors in source XML -->
		<xsl:otherwise>
			<xsl:attribute name="ERROR" select="'This option is not listed among correct, ordered options'"/>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>


<!-- pick up already-assigned entity_id or generate a new one -->
<xsl:template match="@id[parent::question[parent::questions or parent::chapter]]" name="set_atom_id">
	<xsl:variable name="entity_id">
		<xsl:call-template name="get_entity_id"/>
	</xsl:variable>
	<!-- when called by name the context will be the html node rather than @id; this adapts for both situations -->
	<xsl:variable name="find_id" select="current()/parent::*/qid | current()/qid | current()/parent::*/qnum | current()/qnum"/>

	<xsl:attribute name="id">
		<!-- XML node ID prefix for use in both outputs -->
		<xsl:text>q</xsl:text>

		<xsl:choose>
			<!-- useful whenever you want to preseve entity IDs from previously ingested XML; pull from db and put into external XML file --> 
			<xsl:when test="$entity_id != ''">
				<xsl:value-of select="$entity_id"/>
			</xsl:when>
			<!-- typical ID generation: XML node ID prefix + unique ID base for document + generated-id for atom + output of UUID process on atom -->
			<xsl:otherwise>
				<xsl:value-of select="concat('', format-number($entity_id_base, '9999999'), translate($uuid_tree/key('uuid_uniquenode', $find_id)[position()=1]/@genid, 'ghijklmnopqrstuvwxyz', '0123456789abcdef0123'), $uuid_tree/key('uuid_uniquenode', $find_id)[position()=1]/@uuid)"/>
			</xsl:otherwise>
		</xsl:choose>
		
		<!-- ids are required and must be unique, but we don't need a new entity_id in case study/testlet so we add a simple iterating counter to it -->
		<xsl:if test="count(ancestor::question) > 0">
			<xsl:value-of select="concat('_', count(ancestor-or-self::question) - 1, '_', count(preceding-sibling::question) + 1)"/>
		</xsl:if>
	</xsl:attribute>
</xsl:template>


<!-- generates id attributes and populates with distinct values for new nodes requiring them -->
<xsl:template name="id_generator">
	<!-- force a new ID if "true" or a unique ID was sent (eg para_splitter) -->
	<xsl:param name="new" select="'false'"/>
	<!-- selects local-name(), but can send new nodename if it will be changing and the match rule is complicated -->
	<xsl:param name="nodename" select="local-name()"/>
	<!-- METIS adds its own prefix to some elements; strip provided value -->
	<xsl:param name="strip_prefix" select="'false'"/>

	<xsl:variable name="prefix">
		<!-- when done manually, newly inserted nodes would have "q" suffixed to the ID prefix (eg "spanq"); that is not done in METIS --> 
		<xsl:choose>
			<xsl:when test="$nodename = 'para'">p</xsl:when>
			
			<xsl:otherwise>NONE</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:if test="$prefix != 'NONE'">
		<xsl:attribute name="id">
			<xsl:choose>
				<!-- generate a brand new ID number -->
				<xsl:when test="$new != 'false' or not(@id)">
					<xsl:value-of select="$prefix"/>
					<xsl:choose>
						<!-- iterate current ID by 1 if not already used; this is preferred for cleanliness of XML -->
						<xsl:when test="($new = 'true' or $new = 'false') and @id and not(//*[@id = concat($prefix, number(substring-after(@id, $prefix) + 1))])">
							<xsl:value-of select="format-number(number(substring-after(@id, $prefix) + 1), '0000')"/>
						</xsl:when>
						<!-- a unique ID was already generated, convert to numeric; this is used by para_splitter because the ID iterator would result in duplicates -->
						<xsl:when test="$new != 'true' and $new != 'false'">
							<xsl:value-of select="translate($new, 'abcdefghijklmnopqrstuvwxyz', '00000000000000000000000000')"/>
						</xsl:when>
						<!-- generate a unique ID based on the current, unique node and make it numeric -->
						<xsl:otherwise>
							<xsl:value-of select="translate(generate-id(.), 'abcdefghijklmnopqrstuvwxyz', '00000000000000000000000000')"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<!-- use existing ID after stripping off temporary prefix -->
				<xsl:when test="@id and $strip_prefix != 'false'">
					<xsl:value-of select="substring-after(@id, $strip_prefix)"/>
				</xsl:when>
				<!-- use existing ID -->
				<xsl:when test="@id">
					<xsl:value-of select="@id"/>
				</xsl:when>
				<!-- um, whoops -->
				<xsl:otherwise>ERROR</xsl:otherwise>
			</xsl:choose>
		
		</xsl:attribute>
	</xsl:if>

</xsl:template>




<!-- default copy rules for everything else -->
<xsl:template match="node()">
	<xsl:copy>
		<xsl:apply-templates select="@*" mode="#current"/>
		<xsl:apply-templates mode="#current"/>
	</xsl:copy>
</xsl:template>

<xsl:template match="@*">
	<xsl:copy-of select="."/>
</xsl:template>

</xsl:stylesheet>
