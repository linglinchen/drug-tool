<?php
/* these TIFs are likely character replacements and should not be replaced in source this way, but rather with MathML, etc
$searchreplace = array(
ERROR_no_figure_number_found	'/src="_REPLACE_LOCATION__fx1.tif"/' => 'src="f01--9780702032318"', //apothecaries’ weights and measures
ERROR_no_figure_number_found	'/src="_REPLACE_LOCATION__fx2.tif"/' => 'src="f01--9780702032318"', //apothecaries’ weights and measures
ERROR_no_figure_number_found	'/src="_REPLACE_LOCATION__fx1.tif"/' => 'src="f01--9780702032318"', //apothecaries’ weights and measures
ERROR_no_figure_number_found	'/src="_REPLACE_LOCATION__fx1.tif"/' => 'src="f01--9780702032318"', //apothecaries’ weights and measures
ERROR_no_figure_number_found	'/src="_REPLACE_LOCATION__fx1.tif"/' => 'src="f04--9780702032318"', //dram
ERROR_no_figure_number_found	'/src="_REPLACE_LOCATION__fx2.tif"/' => 'src="f15--9780702032318"', //ounce

*/

/* these should be replaced after checking that the filenames match up in random locations and the number of images found matches the number of images on S3
	- images that do not have a number in source are recorded here in such a way they will break the PHP if not commented out
	- images whose figure numbers were found in the wrong location are also recorded so they will break PHP if error message is not removed after verification of numbering
*/
$searchreplace = array(
	'/src="_REPLACE_LOCATION__21896455.jpg"/' => 'src="f01-01-9780702032318"', //abdominal: Pig with distended abdomen due to rectal stricture causing marked distension of the large intestine.
	'/src="_REPLACE_LOCATION__21924288.jpg"/' => 'src="f01-02-9780702032318"', //abdominocentesis: A needle is slowly introduced into the peritoneal cavity on midline 2 to 3 cm caudal to the umbilicus for abdominocentesis.
	'/src="_REPLACE_LOCATION__21896469.jpg"/' => 'src="f01-03-9780702032318"', //abscess: Potential routes of apical infectiion of equine maxillary cheek teeth.
	'/src="_REPLACE_LOCATION__21896473.jpg"/' => 'src="f01-04-9780702032318"', //abscess: Miliary abscesses in the liver of a near-term aborted lamb.
	'/src="_REPLACE_LOCATION__21896479.jpg"/' => 'src="f01-05-9780702032318"', //abscess: Pectoral abscess (pigeon fever) with swelling of the left pectoral region and the lower neck.
	'/src="_REPLACE_LOCATION__21924308.jpg"/' => 'src="f01-06-9780702032318"', //Abyssinian: Abyssinian guinea pig.
	'/src="_REPLACE_LOCATION__21924311.jpg"/' => 'src="f01-07-9780702032318"', //Acanthamoeba: Acanthamoeba trophozoites from culture. Note the filamentous pseudopods and the large central karyosome within the nucleus.
	'/src="_REPLACE_LOCATION__21896475.jpg"/' => 'src="f01-08-9780702032318"', //acanthosis: Acanthosis nigricans in the axillae of a 1-year-old Dachshund.
	//new word
	'/src="_REPLACE_LOCATION__21924328.jpg"/' => 'src="f01-09-9780702032318"', //acid-fast: Ziehl-Neelsen stain. Skin. Dog, mycobacterial dermatitis. Acid-fast organisms (arrowheads) are strongly stained bright red.
	'/src="_REPLACE_LOCATION__21622447.jpg"/' => 'src="f01-10-9780702032318"', //acne: Feline acne.
	'/src="_REPLACE_LOCATION__21896477.jpg"/' => 'src="f01-11-9780702032318"', //acral: Acral lick dermatitis.

	'/src="_REPLACE_LOCATION__21896481.jpg"/' => 'src="f01-12a-9780702032318"', //actinobacillosis: Ewe with actinobacillosis showing the swelling of the lips, nose and frontal areas of the face resulting from multiple abscesses with fistulous tracts. Pus can be seen draining from one fistulous tract.  In contrast to cattle, where actinobacillsosis affects primarily the tongue, infection in sheep is in the lips and facial tissues. The difference is because sheep use predomionantly the lips rather than the tongue for prehension of food.
	'/src="_REPLACE_LOCATION__21896482.jpg"/' => 'src="f01-12b-9780702032318"', //actinobacillosis: Lateral view of the face of the same ewe.
	'/src="_REPLACE_LOCATION__21896485.jpg"/' => 'src="f01-13-9780702032318"', //action potential: Action potential - refractory periods.From Koeppen BM, Stanton BA, Berne &amp; Levy Physiology, 6th ed, Mosby, 2008
	'/src="_REPLACE_LOCATION__21922508.jpg"/' => 'src="f01-14-9780702032318"', //adduction: Adduction.
	'/src="_REPLACE_LOCATION__21623336.jpg"/' => 'src="f01-15-9780702032318"', //adenitis: Idiopathic salivary adenitis in a horse.
	'/src="_REPLACE_LOCATION__21922528.jpg"/' => 'src="f01-16-9780702032318"', //adenocarcinoma: Salivary adenocarcinoma. Individual epithelial cells with round nuclei and lightly basophilic cytoplasm are present in a thick eosinophilic background of secretory material.
	'/src="_REPLACE_LOCATION__21922548.jpg"/' => 'src="f01-17-9780702032318"', //adrenal gland: Adrenal gland. Cross section of an adrenal gland showing cortex and medulla.
	'/src="_REPLACE_LOCATION__21924348.jpg"/' => 'src="f01-18-9780702032318"', //Adson tissue forceps: Adson Tissue Forceps.
	'/src="_REPLACE_LOCATION__21624102.jpg"/' => 'src="f01-19-9780702032318"', //Aelurostrongylus: Larva of Aelurostrongylus abstrusus.
	'/src="_REPLACE_LOCATION__21896488.jpg"/' => 'src="f01-20-9780702032318"', //agar: Colonies of bacteria growing on a blood agar plate. The material to be cultured has been streaked across the plate in a manner to result in an area of individual colonies.
	'/src="_REPLACE_LOCATION__21924368.jpg"/' => 'src="f01-21-9780702032318"', //agglutination: Macroscopic slide agglutination of erythrocytes in a cat with primary IMHA.
	//removed image a-22 avian respirator system
	'/src="_REPLACE_LOCATION__21924408.jpg"/' => 'src="f01-22-9780702032318"', //Alaria: Egg of Alaria species.
	'/src="_REPLACE_LOCATION__21625261.jpg"/' => 'src="f01-23-9780702032318"', //albino: Albino mare and foal.
	'/src="_REPLACE_LOCATION__21896490.jpg"/' => 'src="f01-24-9780702032318"', //allergic: Sweet itch (Queensland itch) on the neck of a horse.
	'/src="_REPLACE_LOCATION__21896492.jpg"/' => 'src="f01-25-9780702032318"', //alley:  Feed alley in a dairy. The cows are feeding through lock-ups on a total-mixed ration that has been delivered by a feed wagon.  Cattle in the pen on the right will be in the milking shed.
	'/src="_REPLACE_LOCATION__21924428.jpg"/' => 'src="f01-26-9780702032318"', //Allis tissue forceps: Allis Tissue Forceps.
	'/src="_REPLACE_LOCATION__21626055.jpg"/' => 'src="f01-27-9780702032318"', //alloimmune: Hemolytic anemia of the newborn.
	'/src="_REPLACE_LOCATION__21896501.jpg"/' => 'src="f01-28-9780702032318"', //alopecia:  Bilaterally symmetrical alopecia caused by self-epilation, resulting in a characteristic ‘racing stripe’ of normal hair.
	'/src="_REPLACE_LOCATION__21922569.jpg"/' => 'src="f01-29-9780702032318"', //alveolar2: Smallest terminal bronchioles divide into alveolar ducts that lead to clusters of alveoli called alveolar sacs.
	'/src="_REPLACE_LOCATION__21897828.jpg"/' => 'src="f01-30-9780702032318"', //Amblyomma: Engorged female Amblyomma americanum. Courtesy of CDC/Dr Amanda Loftis, Dr William Nicholson, Dr Will Reeves, Dr Chris Paddock; Photo: James Gathany.
	'/src="_REPLACE_LOCATION__21924088.jpg"/' => 'src="f01-31-9780702032318"', //Ambu bag: Use of an Ambu bag to deliver room air to an intubated patient.
	'/src="_REPLACE_LOCATION__21922588.jpg"/' => 'src="f01-32-9780702032318"', //ameloblastoma: Canine acanthomatous ameloblastoma.
	//new file missing
	'/src="_REPLACE_LOCATION__21924448.jpg"/' => 'src="f01-33-9780702032318"', //amnion: The feline fetal membranes in transverse and longitudinal section, schematic. 1, Amnion; 2, amniotic cavity; 3, yolk sac; 4, chorioallantois; 5, allantoic cavity; 6, zonary placenta.
	'/src="_REPLACE_LOCATION__21896512.jpg"/' => 'src="f01-34-9780702032318"', //anagen: Anagen defluxion in a calf with chronic calf scours.
	'/src="_REPLACE_LOCATION__21896518.jpg"/' => 'src="f01-35-9780702032318"', //anal: Atresia ani in a piglet.
	'/src="_REPLACE_LOCATION__21896514.jpg"/' => 'src="f01-36-9780702032318"', //anal sacs: Anal sacs in the dog.
	'/src="_REPLACE_LOCATION__21896516.jpg"/' => 'src="f01-37-9780702032318"', //anal sacs: Ruptured anal sac abscess in a dog.
	'/src="_REPLACE_LOCATION__21924468.jpg"/' => 'src="f01-38-9780702032318"', //Anaplasma: Anaplasma maginale (arrows) in bovine erythrocytes.
	'/src="_REPLACE_LOCATION__21628406.jpg"/' => 'src="f01-39-9780702032318"', //Anatolian shepherd dog: Anatolian shepherd dog.
	'/src="_REPLACE_LOCATION__21628499.jpg"/' => 'src="f01-40-9780702032318"', //anconeal, anconal: Ununited anconeal process.
	'/src="_REPLACE_LOCATION__21924508.jpg"/' => 'src="f01-41-9780702032318"', //Ancylostoma: Ancylostoma caninum.
	'/src="_REPLACE_LOCATION__21628549.jpg"/' => 'src="f01-42-9780702032318"', //Andalusian, Andalucian: Andalusian horse.
	'/src="_REPLACE_LOCATION__21924528.jpg"/' => 'src="f01-43-9780702032318"', //anechoic: Ultrasound scan of a urinary bladder in a dog with signs of lower urinary tract infection to show ultrasound principles and artifacts. The bladder (B) is filled with anechoic urine. There is a hyperechogenic stone (arrow) with acoustic shadowing beneath it (S) and distant enhancement (E) on either side of the shadow, distal to the bladder.
	'/src="_REPLACE_LOCATION__21896520.jpg"/' => 'src="f01-44-9780702032318"', //anemia: A-44: The color of the sclera and conjunctiva is used to asses to presence of anemia in the clinical examination of sheep. The marked pallor in this sheep is due to anemia resulting from hemonchosis.
	'/src="_REPLACE_LOCATION__21679954.jpg"/' => 'src="f01-45-9780702032318"', //anesthesia: Needle placement for lumbosacral epidural in the dog.
	'/src="_REPLACE_LOCATION__21896524.jpg"/' => 'src="f01-46-9780702032318"', //angioedema: Angioedema. Severe swelling of the face and periocular tissue caused by a venomous insect sting.
	'/src="_REPLACE_LOCATION__21629292.jpg"/' => 'src="f01-47-9780702032318"', //Anglo-Arab: Anglo-Arab horse.
	'/src="_REPLACE_LOCATION__21629305.jpg"/' => 'src="f01-48-9780702032318"', //Angora: Angora goat.
	'/src="_REPLACE_LOCATION__21922608.jpg"/' => 'src="f01-49-9780702032318"', //anisocoria: Cat with neurological signs including anisocoria due to granulomatous changes in the central nervous system caused by feline infectious peritonitis (FIP).
	//new image missing file
	'/src="_REPLACE_LOCATION__21924568.jpg"/' => 'src="f01-50-9780702032318"', //Anoplura: Linognathus setosus (Anoplura) of dogs and foxes.

	'/src="_REPLACE_LOCATION__21897830.jpg"/' => 'src="f01-51-9780702032318"', //anthrax: Cutaneous anthrax. Courtesy of CDC/James H Steele.
	'/src="_REPLACE_LOCATION__21896530.jpg"/' => 'src="f01-52-9780702032318"', //aortic: Thoracic aorta of a dog with widespread Spirocerca lupi-associated aneurysms and mineralisation of the aortic wall. Bar = 2.5 cm.
	'/src="_REPLACE_LOCATION__21631643.jpg"/' => 'src="f01-53-9780702032318"', //aortic: Aortic thromboembolism in a cat.
	'/src="_REPLACE_LOCATION__21632115.jpg"/' => 'src="f01-54-9780702032318"', //Appaloosa: Appaloosa horse.
	'/src="_REPLACE_LOCATION__21632435.jpg"/' => 'src="f01-55-9780702032318"', //Arab: Arab thoroughbred.
	'/src="_REPLACE_LOCATION__21896538.jpg"/' => 'src="f01-56-9780702032318"', //arthritis: Arthritis. Synovitis and purulent exudate in a joint cavity at postmortem examination.
	'/src="_REPLACE_LOCATION__21633550.jpg"/' => 'src="f01-57-9780702032318"', //arthrography: Arthrogram of the shoulder joint of a dog.
	'/src="_REPLACE_LOCATION__21896540.jpg"/' => 'src="f01-58-9780702032318"', //arthrogryposis: Heifer with lupine-induced arthrogryposis. Most affected calves would not survive, or would not be kept, to this age.
	'/src="_REPLACE_LOCATION__21633705.jpg"/' => 'src="f01-59-9780702032318"', //articular: Erosion of the humeral articular cartilage in a dog viewed by arthroscopy.
	'/src="_REPLACE_LOCATION__21896542.jpg"/' => 'src="f01-60-9780702032318"', //articular: Congenital articular rigidity in a piglet.
	'/src="_REPLACE_LOCATION__21896544.jpg"/' => 'src="f01-61-9780702032318"', //ascariasis: Ascariasis. Liver damage caused by migrating Ascaris suis larvae.
	'/src="_REPLACE_LOCATION__21896546.jpg"/' => 'src="f01-62-9780702032318"', //Ascaris: Ascaris suum.  From Bassert JM, McCurnin DM, McCurnin's Clinical Textbook for Veterinary Technicians, 7th ed, Saunders, 2010
	'/src="_REPLACE_LOCATION__21896548.jpg"/' => 'src="f01-63-9780702032318"', //ascites: Dog with ascites.
	'/src="_REPLACE_LOCATION__21896550.jpg"/' => 'src="f01-64-9780702032318"', //aspergillosis: Depigmentation of the nares of a dog with nasal aspergillosis.
	'/src="_REPLACE_LOCATION__21896554.jpg"/' => 'src="f01-65-9780702032318"', //atopy: Atopy in a West Highland white terrier.
	'/src="_REPLACE_LOCATION__21896556.jpg"/' => 'src="f01-66-9780702032318"', //atrial: Schematic representation of an atrial septal defect.
	'/src="_REPLACE_LOCATION__21896558.jpg"/' => 'src="f01-67-9780702032318"', //atrophic: Atrophic rhinitis of a pig resulting in shortening of the snout.  Courtesy of JT Done.
	'/src="_REPLACE_LOCATION__21896560.jpg"/' => 'src="f01-68-9780702032318"', //atypical interstitial pneumonia: Lung of cow with atypical interstitial pneumonia (fog fever).
	'/src="_REPLACE_LOCATION__21896562.jpg"/' => 'src="f01-69-9780702032318"', //auricular: Aural hematoma. Swollen "ballooning" of the pinna is apparent.
	'/src="_REPLACE_LOCATION__21635333.jpg"/' => 'src="f01-70-9780702032318"', //Australian: Australian kelpie (barb).
	'/src="_REPLACE_LOCATION__21635366.jpg"/' => 'src="f01-71-9780702032318"', //Australian: Australian terrier.

//this image moved from a-22
	'/src="_REPLACE_LOCATION__21924108.jpg"/' => 'src="f01-72-9780702032318"', //avian: Diagram of the avian respiratory system showing location of air sacs.

	'/src="_REPLACE_LOCATION__21922628.jpg"/' => 'src="f02-01-9780702032318"', //Babesia: Trophozoites of Babesia canis within canine red blood cells.
	'/src="_REPLACE_LOCATION__21924588.jpg"/' => 'src="f02-02-9780702032318"', //Backhaus towel clamp: Backhaus Towel Clamp.
	'/src="_REPLACE_LOCATION__21924590.jpg"/' => 'src="f02-03-9780702032318"', //Baermann technique: Baermann apparatus is used to recover larvae of roundworms from feces, soil, or animal tissues.
	'/src="_REPLACE_LOCATION__21922648.jpg"/' => 'src="f02-04-9780702032318"', //Balantidium:  Motile trophozoite stage of Balantidium coli From Hendrix CM, Robinson E, Diagnostic Parasitology for Veterinary Technicians, 3rd Edition. C.V. Mosby, 2006.
	'/src="_REPLACE_LOCATION__21896564.jpg"/' => 'src="f02-05-9780702032318"', //bald: Idiopathic bald thigh syndrome of Greyhounds.
	'/src="_REPLACE_LOCATION__21922668.jpg"/' => 'src="f02-06-9780702032318"', //Balfour retractor: Balfour retractor.
	'/src="_REPLACE_LOCATION__21896566.jpg"/' => 'src="f02-07-9780702032318"', //balling: Method of oral administration of a bolus to a cow using a ballling gun.
	'/src="_REPLACE_LOCATION__21637238.jpg"/' => 'src="f02-08-9780702032318"', //bandage: Robert–Jones bandage.
	'/src="_REPLACE_LOCATION__21924608.jpg"/' => 'src="f02-09-9780702032318"', //barbering: Trauma (cage mate–inflicted hair loss) on a mouse. This is commonly referred to as barbering.
	'/src="_REPLACE_LOCATION__21637438.jpg"/' => 'src="f02-10-9780702032318"', //barium (Ba): Barium enema.
	'/src="_REPLACE_LOCATION__21896570.jpg"/' => 'src="f02-11-9780702032318"', //basal: Basal cell tumor.
	'/src="_REPLACE_LOCATION__21637746.jpg"/' => 'src="f02-12-9780702032318"', //Basenji: Basenji.
	'/src="_REPLACE_LOCATION__21638055.jpg"/' => 'src="f02-13-9780702032318"', //Beagle: Beagle.
	'/src="_REPLACE_LOCATION__21638065.jpg"/' => 'src="f02-14-9780702032318"', //beak: Different shape of beak between species.
	'/src="_REPLACE_LOCATION__21638135.jpg"/' => 'src="f02-15-9780702032318"', //Bearded collie: Bearded collie.
	'/src="_REPLACE_LOCATION__21638164.jpg"/' => 'src="f02-16-9780702032318"', //beat: Palpation of the apex beat.
	'/src="_REPLACE_LOCATION__21638237.jpg"/' => 'src="f02-17-9780702032318"', //Bedlington terrier: Bedlington terrier.
	'/src="_REPLACE_LOCATION__21896574.jpg"/' => 'src="f02-18-9780702032318"', //Bedlington terrier: Jaundice in a Bedlington terrier with copper-associated hepatopathy.
	'/src="_REPLACE_LOCATION__21638526.jpg"/' => 'src="f02-19-9780702032318"', //Belted Galloway: Belted Galloway dual-purpose cattle.
	'/src="_REPLACE_LOCATION__21638883.jpg"/' => 'src="f02-20-9780702032318"', //besnoitiosis: Sclerodermatitis in a cow with besnoitiosis.
	'/src="_REPLACE_LOCATION__21922688.jpg"/' => 'src="f02-21-9780702032318"', //bigeminy: Ventricular bigeminy (cat). Note that normal complexes (the odd numbered complexes) alternate with ventricular premature complexes.
	'/src="_REPLACE_LOCATION__21896578.jpg"/' => 'src="f02-22-9780702032318"', //bilirubinuria: Bilirubinuria in a foal with Tyzzer's disease.
	'/src="_REPLACE_LOCATION__21639534.jpg"/' => 'src="f02-23-9780702032318"', //binocular: Field of vision of predatory animals.
	'/src="_REPLACE_LOCATION__21639796.jpg"/' => 'src="f02-24-9780702032318"', //biopsy: Needle aspiration of bone marrow.
	'/src="_REPLACE_LOCATION__21896580.jpg"/' => 'src="f02-25-9780702032318"', //black spot: Black spot lesion on the teat of a cow.
	'/src="_REPLACE_LOCATION__21896584.jpg"/' => 'src="f02-26-9780702032318"', //blepharitis: Staphylococcal blepharitis in a Weimaraner.
	//image 27 removed. thown off numbering
	'/src="_REPLACE_LOCATION__21641240.jpg"/' => 'src="f02-27-9780702032318"', //Blonde D'Aquitaine: Blonde D'Aquatine beef bull.
	'/src="_REPLACE_LOCATION__21896595.jpg"/' => 'src="f02-28a-9780702032318"', //bluetongue: Erosive lesions of bluetongue with hyperemia and ulceration on the vulva of a ewe.
	'/src="_REPLACE_LOCATION__21896597.jpg"/' => 'src="f02-28b-9780702032318"', //bluetongue: Erosive lesions on the tongue and lips in bluetongue.
	'/src="_REPLACE_LOCATION__21897832.jpg"/' => 'src="f02-29-9780702032318"', //bobcat: Bobcat (Lynx rufus). Courtesy of CDC.
	'/src="_REPLACE_LOCATION__21922708.jpg"/' => 'src="f02-30-9780702032318"', //body: New methylene blue–stained blood from a cat showing Heinz bodies projecting from red blood cells.
	'/src="_REPLACE_LOCATION__21642069.jpg"/' => 'src="f02-31-9780702032318"', //Boer: Boer dual-purpose sheep.
	'/src="_REPLACE_LOCATION__21642279.jpg"/' => 'src="f02-32-9780702032318"', //bone: Aneurysmal bone cyst.
	'/src="_REPLACE_LOCATION__21642326.jpg"/' => 'src="f02-33-9780702032318"', //bone: Structure of typical long bone.
	'/src="_REPLACE_LOCATION__21642545.jpg"/' => 'src="f02-34-9780702032318"', //Boran: Boran dairy cattle.
	'/src="_REPLACE_LOCATION__21896603.jpg"/' => 'src="f02-35-9780702032318"', //border disease: Polypay lambs with border disease showing phenotypic abnormality for the breed, abnormal hair coat and one with abnormal coloring.
	'/src="_REPLACE_LOCATION__21924068.jpg"/' => 'src="f02-36-9780702032318"', //Bordetella: Columnar epithelium with dark-staining bacterial rods of Bordetella bronchisepticatightly adhered to cilia.
	'/src="_REPLACE_LOCATION__21896605.jpg"/' => 'src="f02-37-9780702032318"', //bots: Horse bots (Gasterophilusspp.)cemented to hair on the forelimb of a horse.
	'/src="_REPLACE_LOCATION__21642851.jpg"/' => 'src="f02-38-9780702032318"', //Bouvier des Flandres: Bouvier des Flandres with uncropped ears.
	'/src="_REPLACE_LOCATION__21896607.jpg"/' => 'src="f02-39-9780702032318"', //bovine: Acute stomatitis in bovine virus diarrhea/mucosal disease.
	'/src="_REPLACE_LOCATION__21924628.jpg"/' => 'src="f02-40-9780702032318"', //Boxer: Boxer.
	'/src="_REPLACE_LOCATION__21922728.jpg"/' => 'src="f02-41-9780702032318"', //brachial: The brachial plexus. The ventral divisions of the spinal nerves (C6–T2) contributing to the plexus are at the top of the schema, the peripheral branches (N) supplying the forelimb at the bottom.
	'/src="_REPLACE_LOCATION__21924630.jpg"/' => 'src="f02-42-9780702032318"', //brachycephalic: Dorsolateral aspect of the skull of a brachycephalic breed of dog, demonstrating the foreshortened facial region and the malocclusion of the dental arcade.
	'/src="_REPLACE_LOCATION__21643295.jpg"/' => 'src="f02-43-9780702032318"', //bradycardia: Sinus bradycardia in a dog.
	'/src="_REPLACE_LOCATION__21922748.jpg"/' => 'src="f02-44-9780702032318"', //bradyzoite: Transverse section of feline skeletal muscle showing bradyzoite of Toxoplasma gondii.
	'/src="_REPLACE_LOCATION__21896609.jpg"/' => 'src="f02-45-9780702032318"', //brand: Freeze branding. The number 99 on the rump of the cow is the result of freeze branding which results in the depigmentation of pigmented skin.
	'/src="_REPLACE_LOCATION__21897730.jpg"/' => 'src="f02-46-9780702032318"', //break: Wool break. Fever and illness caused by bluetongue in the recent past caused weakness of fiber produced at that time. Subsequent wool growth after recovery is normal (A) but the wool produced (B) before the illness is falling off because of the weakness in fiber (C).
	'/src="_REPLACE_LOCATION__21922768.jpg"/' => 'src="f02-47-9780702032318"', //bronchiectasis: Severe bronchiectasis with chronic bronchopneumonia in the right lung of a calf.   The lumens of affected bronchi are filled with purulent exudate.  From McGavin MD, Zachary J, Pathologic Basis of Veterinary Disease, 4th Edition. Mosby, 2007.
	'/src="_REPLACE_LOCATION__21896615.jpg"/' => 'src="f02-48-9780702032318"', //bronchopneumonia: Severe subacute bronchopneumonia in a puppy.
	'/src="_REPLACE_LOCATION__21644575.jpg"/' => 'src="f02-49-9780702032318"', //Brown Swiss cattle: Brown Swiss dual-purpose bull.
	'/src="_REPLACE_LOCATION__21644963.jpg"/' => 'src="f02-50-9780702032318"', //Budyonny horse: Budyonny horse.
	'/src="_REPLACE_LOCATION__21922788.jpg"/' => 'src="f02-51-9780702032318"', //Bufo: The Bufo spp. toads produce a powerful, potentially toxic secretion from the parotid glands.

	'/src="_REPLACE_LOCATION__21645300.jpg"/' => 'src="f02-52-9780702032318"', //bulldog calves: Bulldog calf.
		//b-54 removed (burdizzo emasculatome)
	'/src="_REPLACE_LOCATION__21896624.jpg"/' => 'src="f02-53-9780702032318"', //burr: Scrotal lesions of mycotic dermatitis initiated by seed burrs (arrow).
	'/src="_REPLACE_LOCATION__21896628.jpg"/' => 'src="f02-54-9780702032318"', //bushfire injury: Grass fire burns in a cow.There have been severe burns to the skin with damage to the conjunctiva and cornea, as indicated by conjunctival swelling, and to the nasal mucous membranes.

	'/src="_REPLACE_LOCATION__21646308.jpg"/' => 'src="f03-01-9780702032318"', //Cairn terrier: Cairn terrier.
	'/src="_REPLACE_LOCATION__21896639.jpg"/' => 'src="f03-02-9780702032318"', //calcinosis: Radiograph of calcinosis circumscripta on the carpus of a dog.
	'/src="_REPLACE_LOCATION__21646470.jpg"/' => 'src="f03-03-9780702032318"', //calcinosis: Calcinosis cutis in a dog with Cushing's syndrome.
	'/src="_REPLACE_LOCATION__21896641.jpg"/' => 'src="f03-04-9780702032318"', //calcium (Ca): Osteomalacia resulting from a diet of stored grain fed as the only food available for feeding during a drought which resulted in bare pastures.
	'/src="_REPLACE_LOCATION__21924648.jpg"/' => 'src="f03-05-9780702032318"', //calcium (Ca): Calcium oxalate dihydrate crystals in a dog.
	'/src="_REPLACE_LOCATION__21896643.jpg"/' => 'src="f03-06-9780702032318"', //calf: Calf pen with three bucket holes, one for feeding calf starter, one for providing water all the time and one for the bucket used to feed milk replacer.
	'/src="_REPLACE_LOCATION__21896645.jpg"/' => 'src="f03-07-9780702032318"', //calicivirus: Tongue ulcers in a cat with feline calicivirus infection.
	'/src="_REPLACE_LOCATION__21924653.jpg"/' => 'src="f03-08-9780702032318"', //calipers: Caliper used to measure an animal's body parts so the settings on a radiograph machine can be determined.From Sonsthagen TF, Veterinary Instruments and Equipment: A Pocket Guide, ed 2. 2011, Mosby.
	'/src="_REPLACE_LOCATION__21922808.jpg"/' => 'src="f03-09-9780702032318"', //CAMP phenomenon: Augmentation of staphylococcal β-hemolysis by Streptococcus agalactiae CAMP protein (CAMP reaction).
	'/src="_REPLACE_LOCATION__21924656.jpg"/' => 'src="f03-10-9780702032318"', //Campylobacter: Gram-stained cells of Campylobacter fetus ssp. fetus.
	'/src="_REPLACE_LOCATION__21896647.jpg"/' => 'src="f03-11-9780702032318"', //canker: Severe canker in the frog of a horse's hoof.
	'/src="_REPLACE_LOCATION__21896649.jpg"/' => 'src="f03-12-9780702032318"', //capital: Fracture of the capital epiphysis of the right femur in a 6-month-old Collie.
	'/src="_REPLACE_LOCATION__21648251.jpg"/' => 'src="f03-13-9780702032318"', //carapace: Carapace of the tortoise.
	'/src="_REPLACE_LOCATION__21896656.jpg"/' => 'src="f03-14-9780702032318"', //carcinoma: Carcinoma on the nose of a whiteface sheep.
	'/src="_REPLACE_LOCATION__21896658.jpg"/' => 'src="f03-15-9780702032318"', //cardiomyopathy: Cross section of the heart from a cat with hypertrophic cardiomyopathy showing gross thickening of the left ventricular wall and papillary muscles.
	'/src="_REPLACE_LOCATION__21896660.jpg"/' => 'src="f03-16-9780702032318"', //carrier: Many infectious agents are carried by animals at sites not associated with overt disease but these carriage sites provide the source for overt infection when risk factors that allow overt disease to occur. Dermatophilus congolensis is present in this minor lesion but can be spread by flies from this to other regions of the skin to result in clinical dermatophilosis when excessive skin moisture or trauma allows infection.
	'/src="_REPLACE_LOCATION__21896662.jpg"/' => 'src="f03-17-9780702032318"', //cascade: Simplified illustration of the arachidonic acid cascade.
	'/src="_REPLACE_LOCATION__21896665.jpg"/' => 'src="f03-18-9780702032318"', //caseous lymphadenitis: Caseous lymphadenitis. Abscesses in lung of sheep, which when ruptured into a bronchus these provide a source of infection to other sheep through infected nasal discharges.
	'/src="_REPLACE_LOCATION__21649694.jpg"/' => 'src="f03-19-9780702032318"', //cast: Red cell cast in urine sediment.
	'/src="_REPLACE_LOCATION__21897868.jpg"/' => 'src="f03-20-9780702032318"', //cataract: Congenital cataract in a foal.
	'/src="_REPLACE_LOCATION__21650202.jpg"/' => 'src="f03-21-9780702032318"', //catheter: Balloon-tipped angiographic (Berman) catheter.
	'/src="_REPLACE_LOCATION__21650212.jpg"/' => 'src="f03-22-9780702032318"', //catheter: Butterfly catheter.
	'/src="_REPLACE_LOCATION__21651135.jpg"/' => 'src="f03-23-9780702032318"', //cell: Structure of the cell as seen by light microscopy.
	'/src="_REPLACE_LOCATION__21665025.jpg"/' => 'src="f03-24-9780702032318"', //cell: Cell cycle.
	'/src="_REPLACE_LOCATION__21896683.jpg"/' => 'src="f03-25-9780702032318"', //cell: Cell to cell communication.
	'/src="_REPLACE_LOCATION__21651581.jpg"/' => 'src="f03-26-9780702032318"', //Centaurea: Centaurea solstitialis.
	'/src="_REPLACE_LOCATION__21896685.jpg"/' => 'src="f03-27-9780702032318"', //cere: Hypertrophy of the cere and overgrowth of the beak in a budgerigar.
	'/src="_REPLACE_LOCATION__21896687.jpg"/' => 'src="f03-28-9780702032318"', //cerebellar: Cerebellar hypoplasia.
	'/src="_REPLACE_LOCATION__21896689.jpg"/' => 'src="f03-29-9780702032318"', //chalazion: Chalazion in a dog.
	'/src="_REPLACE_LOCATION__21652855.jpg"/' => 'src="f03-30-9780702032318"', //Charolais: Charolais beef bull.
	'/src="_REPLACE_LOCATION__21896691.jpg"/' => 'src="f03-31-9780702032318"', //chemosis: Severe chemosis of the right eye of a goat.
	'/src="_REPLACE_LOCATION__21653232.jpg"/' => 'src="f03-32-9780702032318"', //cherry eye: Cherry eye.
	'/src="_REPLACE_LOCATION__21924048.jpg"/' => 'src="f03-33-9780702032318"', //chestnut: Location of chestnuts in horses
	'/src="_REPLACE_LOCATION__21653299.jpg"/' => 'src="f03-34-9780702032318"', //Cheyletiella: An adult Cheyletiellamite.
	'/src="_REPLACE_LOCATION__21896693.jpg"/' => 'src="f03-35-9780702032318"', //cheyletiellosis: Cheyletiellosis in a cat with diffuse scaling and erythema.
	'/src="_REPLACE_LOCATION__21653338.jpg"/' => 'src="f03-36-9780702032318"', //Chianina cattle: Chianina dual-purpose bull.
	'/src="_REPLACE_LOCATION__21653507.jpg"/' => 'src="f03-37-9780702032318"', //chin: Fat-chin in a cat.
	'/src="_REPLACE_LOCATION__21896695.jpg"/' => 'src="f03-38-9780702032318"', //Chinese crested: Chinese crested dog. A normally hairless breed.
	'/src="_REPLACE_LOCATION__21924668.jpg"/' => 'src="f03-39-9780702032318"', //Chlamydia: Chlamydia psittaci in a direct fluorescent antibody–stained impression smear of infected mouse brain.
	'/src="_REPLACE_LOCATION__21924671.jpg"/' => 'src="f03-40-9780702032318"', //choana: The glottis (yellow arrow) opens from the floor of the snake's oral cavity into the choana (red arrow) on the dorsal surface of the mouth.
	'/src="_REPLACE_LOCATION__21896697.jpg"/' => 'src="f03-41-9780702032318"', //choke: Ingesta at the nostrils of a foal with choke.
	'/src="_REPLACE_LOCATION__21896699.jpg"/' => 'src="f03-42-9780702032318"', //chondrodysplasia: Texel lamb with chondrodysplasia. Note the short legs and wide-based stance.
	'/src="_REPLACE_LOCATION__21896701.jpg"/' => 'src="f03-43-9780702032318"', //chondrosarcoma: Simmental cow with swellling at the proximocranial aspect of the right scapula due to a chondrosarcoma.
	'/src="_REPLACE_LOCATION__21896703.jpg"/' => 'src="f03-44-9780702032318"', //chorda: Ruptured chorda tendineae in a horse heart at necropsy.
	'/src="_REPLACE_LOCATION__21897760.jpg"/' => 'src="f03-45a-9780702032318"', //chorioptic mange: Lesions of chorioptic mange on the escutcheon of a Jersey cow, a common site for infestation.
	'/src="_REPLACE_LOCATION__21897757.jpg"/' => 'src="f03-45b-9780702032318"', //chorioptic mange: Infestation of the poll of a ram with sheep-adapted Chorioptes bovis (poll mites).
	'/src="_REPLACE_LOCATION__21896711.jpg"/' => 'src="f03-46-9780702032318"', //choroidal: Choroidal hypoplasia and coloboma of the optic nerve head in a Collie.
	'/src="_REPLACE_LOCATION__21654877.jpg"/' => 'src="f03-47-9780702032318"', //Chow Chow: Chow chow.
	'/src="_REPLACE_LOCATION__21896713.jpg"/' => 'src="f03-48-9780702032318"', //chute: Cow restrained in squeeze chute. Head catch is controlled by lever A, side squeeze by lever B and tail gate C by a lever hidden behind operator in this picture.
	'/src="_REPLACE_LOCATION__21922828.jpg"/' => 'src="f03-49-9780702032318"', //chylous: Chylous effusion. Pleural. Cat. A pink tint is found in this chylous effusion, indicating some degree of hemorrhage is present.
	'/src="_REPLACE_LOCATION__21655550.jpg"/' => 'src="f03-50-9780702032318"', //ciliary: Anatomy of the ciliary processes.
	'/src="_REPLACE_LOCATION__21924688.jpg"/' => 'src="f03-51-9780702032318"', //circuit: Bain's coaxial system: Fresh gas enters via the clear tube; exhaust gas leaves via the reservoir bag and black hose.
	'/src="_REPLACE_LOCATION__21896715.jpg"/' => 'src="f03-52-9780702032318"', //cirrhosis: Macronodular cirrhosis of the liver in a dog.
	'/src="_REPLACE_LOCATION__21655948.jpg"/' => 'src="f03-53-9780702032318"', //cisternal: Cisternal puncture.
	'/src="_REPLACE_LOCATION__21924708.jpg"/' => 'src="f03-54-9780702032318"', //Citrobacter: Lactose-fermenting colonies on MacConkey agar. Klebsiella, Enterobacter, and Citrobacter spp.
	'/src="_REPLACE_LOCATION__21896717.jpg"/' => 'src="f03-55-9780702032318"', //clamp: Umbilical clamp on the umbilicus of a newborn foal.
	'/src="_REPLACE_LOCATION__21656093.jpg"/' => 'src="f03-56-9780702032318"', //clamp: Atraumatic vascular clamps.
	'/src="_REPLACE_LOCATION__21656370.jpg"/' => 'src="f03-57-9780702032318"', //cleft: Cleft lip (harelip) in a calf.
	'/src="_REPLACE_LOCATION__21896721.jpg"/' => 'src="f03-58-9780702032318"', //cleft: Cleft palate in a newborn puppy.
	'/src="_REPLACE_LOCATION__21896723.jpg"/' => 'src="f03-59-9780702032318"', //coat: Winter coat (arrows) now largely shed by this bull in Spring.
	'/src="_REPLACE_LOCATION__21657491.jpg"/' => 'src="f03-60-9780702032318"', //coccidioidomycosis: Disseminated coccidioidomycosis in the radius and ulna of a dog.
	'/src="_REPLACE_LOCATION__21896725.jpg"/' => 'src="f03-61-9780702032318"', //coccidiosis: Lesions of coccidiosis in the mucosa of the small intestine of a goat.
	'/src="_REPLACE_LOCATION__21657655.jpg"/' => 'src="f03-62-9780702032318"', //Cocker spaniel: English cocker spaniel.
	'/src="_REPLACE_LOCATION__21896727.jpg"/' => 'src="f03-63-9780702032318"', //cold: Cold sterilization tray.
	'/src="_REPLACE_LOCATION__21658067.jpg"/' => 'src="f03-64-9780702032318"', //coldblood: South-German coldblood horse.
	'/src="_REPLACE_LOCATION__21896729.jpg"/' => 'src="f03-65-9780702032318"', //colibacillosis: Septicemic colibacillosis. Calf that has just been rotated to lie on other side showing shock with open moth breathing  and lack of response to surroundings.
	'/src="_REPLACE_LOCATION__21897464.jpg"/' => 'src="f03-66-9780702032318"', //colic: A horse with colic, rolling.
	'/src="_REPLACE_LOCATION__21658439.jpg"/' => 'src="f03-67-9780702032318"', //Collie: Collie (smooth), blue merle color.
	'/src="_REPLACE_LOCATION__21896731.jpg"/' => 'src="f03-68-9780702032318"', //Collie: Collie nose.
	'/src="_REPLACE_LOCATION__21896733.jpg"/' => 'src="f03-69-9780702032318"', //colostrometer:  Colostrometer. A commercially available hygrometer for measurement of immunoglobulin concentraion in colostrum.  Commonly used for this purpose but there are limitations to its accuracy for this purpose.
	'/src="_REPLACE_LOCATION__21659126.jpg"/' => 'src="f03-70-9780702032318"', //comminuted: Comminuted fracture of the tibia.
	'/src="_REPLACE_LOCATION__21894094.jpg"/' => 'src="f03-71-9780702032318"', //compression: Compression screws used in fracture repair in horses.
	'/src="_REPLACE_LOCATION__21659721.jpg"/' => 'src="f03-72-9780702032318"', //conduction: Conduction system of the heart.
	'/src="_REPLACE_LOCATION__21896735.jpg"/' => 'src="f03-73-9780702032318"', //congenital tremor syndrome: Cerebellar hypoplasia in piglet with congenital trembles due to congenital classical swine fever virus infection. This is a pathognomonic postmortem finding for this cause of congenital trembles. Courtesy of JT Done.
	'/src="_REPLACE_LOCATION__21896737.jpg"/' => 'src="f03-74-9780702032318"', //Conium maculatum: Conium maculatum (poison hemlock) showing the umbellifera flower head and red spotting on stalk.
	'/src="_REPLACE_LOCATION__21660096.jpg"/' => 'src="f03-75-9780702032318"', //Connemara pony: Connemara pony.
	'/src="_REPLACE_LOCATION__21660437.jpg"/' => 'src="f03-76-9780702032318"', //contracted: Contracted foal syndrome.
	'/src="_REPLACE_LOCATION__21896739.jpg"/' => 'src="f03-77-9780702032318"', //convulsion: The leveling of the dead pasture and the disturbance of the soil around the feet of this dead ewe are strong indications that she died having terminal convulsions.  The froth at the nose indicates terminal pulmonary edema.  Such observations can help form a tentative diagnosis list for further examination at postmortem.
	'/src="_REPLACE_LOCATION__21896741.jpg"/' => 'src="f03-78-9780702032318"', //copper (Cu): Coat color change in molybdenum-induced secondary copper deficiency. This Angus heifer shows a coat that has lost its luster and whose individual hair fibers have changed in color from black to red and gray.
	'/src="_REPLACE_LOCATION__21897834.jpg"/' => 'src="f03-79-9780702032318"', //copperhead snakes: Agkistrodon contortrix (Southern copperhead). Courtesy of CDC/James Gathany.
	'/src="_REPLACE_LOCATION__21661134.jpg"/' => 'src="f03-80-9780702032318"', //Cori cycle: Cori cycle.
	'/src="_REPLACE_LOCATION__21896743.jpg"/' => 'src="f03-81-9780702032318"', //corkscrew: Corkscrew claws in a Hereford cow.
	'/src="_REPLACE_LOCATION__21924728.jpg"/' => 'src="f03-82-9780702032318"', //corneal: Diffuse corneal edema in a dog with glaucoma.
	'/src="_REPLACE_LOCATION__21661324.jpg"/' => 'src="f03-83-9780702032318"', //corneal: Corneal ulcer in a horse.
	'/src="_REPLACE_LOCATION__21896745.jpg"/' => 'src="f03-84-9780702032318"', //corneal:  Corneal vascularization in a heifer with infectious bovine keratoconjunctivitis.
	'/src="_REPLACE_LOCATION__21896747.jpg"/' => 'src="f03-85-9780702032318"', //cradle: Neck cradle on a horse.
	'/src="_REPLACE_LOCATION__21896749.jpg"/' => 'src="f03-86-9780702032318"', //cranium: Inherited cranium bifidum with meningocele.
	'/src="_REPLACE_LOCATION__21896751.jpg"/' => 'src="f03-87-9780702032318"', //creep:  Piglet creep area. The optimal ambient temperature for young piglets is above 80°F while that for the sow is 65°F. The heated creep area attracts the piglets and reduces the risk for hypothermia and crushing injuries. Creep feed may also be available in this area of the farrowing pen.
	'/src="_REPLACE_LOCATION__21663184.jpg"/' => 'src="f03-88-9780702032318"', //crib: Incisor teeth of a crib biting horse.
	'/src="_REPLACE_LOCATION__21896753.jpg"/' => 'src="f03-89-9780702032318"', //cruciate: Cranial cruciate ligament rupture.
	'/src="_REPLACE_LOCATION__21663962.jpg"/' => 'src="f03-90-9780702032318"', //cryptococcosis: Cryptococcal granuloma.
	'/src="_REPLACE_LOCATION__21664098.jpg"/' => 'src="f03-91-9780702032318"', //crystalluria: Crystalluria: calcium phosphate crystals in urine sediment.
	'/src="_REPLACE_LOCATION__21664714.jpg"/' => 'src="f03-92-9780702032318"', //Cushing's syndrome: Canine Cushing’s syndrome.
	'/src="_REPLACE_LOCATION__21896755.jpg"/' => 'src="f03-93-9780702032318"', //cyanosis: Cyanosis.
	'/src="_REPLACE_LOCATION__21896757.jpg"/' => 'src="f03-94-9780702032318"', //cyst: Multiple anterior uveal cysts in a dog.
	'/src="_REPLACE_LOCATION__21896759.jpg"/' => 'src="f03-95-9780702032318"', //cysticercosis: Cysticercosis with cysts in the omentum.  From van Dijk JE, Gruys E, Mouwen JMVM, Color Atlas of Veterinary Pathology, 2nd ed, Saunders, 2007
	'/src="_REPLACE_LOCATION__21896761.jpg"/' => 'src="f03-96-9780702032318"', //cystitis: Ultrasonographic image of polypoid cystitis in a dog showing multiple polypoid projections (arrows).
	'/src="_REPLACE_LOCATION__21665655.jpg"/' => 'src="f03-97-9780702032318"', //cystocentesis: Collection of a urine sample through ultrasound-guided cystocentesis.
	'/src="_REPLACE_LOCATION__21922848.jpg"/' => 'src="f04-01-9780702032318"', //dacryocystitis, dacrocystitis: Severe dacryocystitis in a Weimaraner with multifocal cutaneous draining tracts. Dziezyc J, Millchamp N, Color Atlas of Canine and Feline Ophthalmology. Saunders, 2005.
	'/src="_REPLACE_LOCATION__21666532.jpg"/' => 'src="f04-02-9780702032318"', //Dandie Dinmont terrier: Dandie Dinmont terrier.
	'/src="_REPLACE_LOCATION__21666647.jpg"/' => 'src="f04-03-9780702032318"', //Dartmoor pony: Dartmoor pony.
	'/src="_REPLACE_LOCATION__21897836.jpg"/' => 'src="f04-04-9780702032318"', //deer: Mule deer (Odocoileus hemionus) the major host of the adult winter ticks, Dermacentor albipictus. Courtesy of CDC.
	'/src="_REPLACE_LOCATION__21922868.jpg"/' => 'src="f04-05-9780702032318"', //defibrillator: Defibrillator. Bassert JM, McCurnin DM, McCurnin's Clinical Textbook for Veterinary Technicians, 7th Edition. Saunders, 2010.
	'/src="_REPLACE_LOCATION__21896526.jpg"/' => 'src="f04-06-9780702032318"', //deformity: Bilateral carpal and metacarpo-phalangeal varus angular deformity in a foal.
	'/src="_REPLACE_LOCATION__21896768.jpg"/' => 'src="f04-07-9780702032318"', //dehorner: Use of a Barnes dehorner or scoop for removal of the horns in a calf.
	'/src="_REPLACE_LOCATION__21667879.jpg"/' => 'src="f04-08-9780702032318"', //Demodex: Demodex canismites.
	'/src="_REPLACE_LOCATION__21897762.jpg"/' => 'src="f04-09a-9780702032318"', //demodicosis: Localized demodicosis on the foreleg of a dog.
	'/src="_REPLACE_LOCATION__21897763.jpg"/' => 'src="f04-09b-9780702032318"', //demodicosis: Generalized demodicosis.
	'/src="_REPLACE_LOCATION__21896772.jpg"/' => 'src="f04-10-9780702032318"', //dental: Dental (dentigerous) cyst with a draining tract in a horse.
	'/src="_REPLACE_LOCATION__21896774.jpg"/' => 'src="f04-11-9780702032318"', //dental:  Opened mouth of a cow showing the lower incisors and the dental pad on the upper jaw.  Because of the angle of the incisor teeth in the lower jaw and their absence in the upper jaw cattle cannot inflict a painful bite with their incisors but can inflict severe crushing injury with their molar teeth if the hand is inserted further back in the oral cavity.
	'/src="_REPLACE_LOCATION__21668135.jpg"/' => 'src="f04-12-9780702032318"', //dental: Dental star.
	'/src="_REPLACE_LOCATION__21668346.jpg"/' => 'src="f04-13-9780702032318"', //depigmentation: Depigmentation (dudley Nose).
	'/src="_REPLACE_LOCATION__21897838.jpg"/' => 'src="f04-14-9780702032318"', //Dermacentor: Dermacentor andersoni. Courtesy of CDC/Dr Christopher Paddock; Photo: James Gathney.
	'/src="_REPLACE_LOCATION__21670682.jpg"/' => 'src="f04-15-9780702032318"', //dermatitis: Clinical presentation of different stages of digital dermatitis. (a) M1 (within red oval): Early stage lesion; mostly 0–2 cm; not painful on palpation. (b) M2 (‘active infection’): classical ulcerative stage; diameter &gt;2 cm, often painful on palpation. (c) M3: healing stage after local therapy; lesion is covered by a scab; not painful on palpation. (d) M4: chronic stage; dyskeratosis or proliferation of surface; generally not painful on palpation.
	'/src="_REPLACE_LOCATION__21897766.jpg"/' => 'src="f04-16a-9780702032318"', //dermatitis: Facial fold dermatitis in a Bulldog.
	'/src="_REPLACE_LOCATION__21897767.jpg"/' => 'src="f04-16b-9780702032318"', //dermatitis: Lip fold dermatitis in a dog.
	'/src="_REPLACE_LOCATION__21897768.jpg"/' => 'src="f04-16c-9780702032318"', //dermatitis:  Body fold dermatitis in a Shar pei.
	'/src="_REPLACE_LOCATION__21752403.jpg"/' => 'src="f04-17-9780702032318"', //dermatitis:  Photosensitive dermatitis with swelling of the face, eyelids and ears.
	'/src="_REPLACE_LOCATION__21896780.jpg"/' => 'src="f04-18-9780702032318"', //dermatitis: Seborrheic dermatitis.
	'/src="_REPLACE_LOCATION__21896782.jpg"/' => 'src="f04-19-9780702032318"', //dermatitis: Feline solar dermatosis.
	'/src="_REPLACE_LOCATION__21896790.jpg"/' => 'src="f04-20-9780702032318"', //dermatosis: Zinc-responsive dermatosis in a Siberian husky.
	'/src="_REPLACE_LOCATION__21896792.jpg"/' => 'src="f04-21-9780702032318"', //dermoid: Temporal limbal dermoid in a dog.
	'/src="_REPLACE_LOCATION__21669018.jpg"/' => 'src="f04-22-9780702032318"', //descemetocele: Descemetocele. FromMaggs, David. Slatter's Fundamentals of Veterinary Ophthalmology, 4th Edition. Saunders, 2008.
	'/src="_REPLACE_LOCATION__21924748.jpg"/' => 'src="f04-23-9780702032318"', //Desmarres: Desmarres forceps.
	'/src="_REPLACE_LOCATION__21669459.jpg"/' => 'src="f04-24-9780702032318"', //Dexter: Dexter beef bull.
	//missing image for 'diabetes mellitus: Plantigrade posture in a cat with diabetes mellitus and exocrine'
	'/src="_REPLACE_LOCATION__21924768.jpg"/' => 'src="f04-25-9780702032318"', //diabetes mellitus: Plantigrade posture in a cat with diabetes mellitus and exocrine pancreatic insufficiency.
	'/src="_REPLACE_LOCATION__21922908.jpg"/' => 'src="f04-26-9780702032318"', //diapedesis: Diapedesis. 1, Neutrophil lying against vessel wall begins to squeeze through the space between endothelial cells by flowing into pseudopod. 2, Pseudopod continues to push its way between cells.  3, Pseudopod and the rest of the cell emerge on tissue side of blood vessel. 4, Neutrophil is off in tissue space. Colville TP, Bassert JM, Clinical Anatomy and Physiology for Veterinary Technicians, 2nd Edition, Mosby, 2007.
	'/src="_REPLACE_LOCATION__21896794.jpg"/' => 'src="f04-27-9780702032318"', //diaphragmatic: Diaphragmatic hernia at post mortem showing intestines and omentum in the thoracic cavity displacing liver and stomach caudally.
	'/src="_REPLACE_LOCATION__21896796.jpg"/' => 'src="f04-28-9780702032318"', //diascopy: Diascopy.
	'/src="_REPLACE_LOCATION__21896800.jpg"/' => 'src="f04-29-9780702032318"', //diphtheria: Necrotizing laryngitis in calf diphtheria.
	'/src="_REPLACE_LOCATION__21922928.jpg"/' => 'src="f04-30-9780702032318"', //Dipylidium: Egg packet of Dipylidium caninum.
	'/src="_REPLACE_LOCATION__21896802.jpg"/' => 'src="f04-31-9780702032318"', //discharge: Vaginal discharge from a mare with placentitis.
	'/src="_REPLACE_LOCATION__21672135.jpg"/' => 'src="f04-32-9780702032318"', //distemper: Distemper teeth.
	'/src="_REPLACE_LOCATION__21672147.jpg"/' => 'src="f04-33-9780702032318"', //distichiasis: D-33Distichiasis.
	'/src="_REPLACE_LOCATION__21896806.jpg"/' => 'src="f04-34-9780702032318"', //dog-sitting posture: D-34 Dog-sitting posture in a piglet due to painful arthritis.
	'/src="_REPLACE_LOCATION__21896808.jpg"/' => 'src="f04-35-9780702032318"', //domed forehead: D-35Domed forehead of a premature foal.
	'/src="_REPLACE_LOCATION__21673042.jpg"/' => 'src="f04-36-9780702032318"', //Don horse: D-36Don horse.
	'/src="_REPLACE_LOCATION__21673051.jpg"/' => 'src="f04-37-9780702032318"', //donkey: D-37Donkey.
	'/src="_REPLACE_LOCATION__21924808.jpg"/' => 'src="f04-38-9780702032318"', //drepanocyte: Sickle cells (drepanocytes) in a blood film from a normal deer.
	'/src="_REPLACE_LOCATION__21674401.jpg"/' => 'src="f04-39-9780702032318"', //Duroc Jersey: D-39Duroc pig.
	'/src="_REPLACE_LOCATION__21674856.jpg"/' => 'src="f04-40-9780702032318"', //dyspnea: D-40Sixteen-year-old cat with dyspnea and open mouth breathing caused by thoracic effusion due to FIP.
	'/src="_REPLACE_LOCATION__21896814.jpg"/' => 'src="f04-41-9780702032318"', //dystocia: D-41Dystocia may damage the dam or the newborn. This calf has swelling of the tongue and face due to prolonged birth while the head was outside the vagina, and will have difficulty nursing the cow and getting an early intake of colostrum. Courtesy O Szenci.
	'/src="_REPLACE_LOCATION__21896816.jpg"/' => 'src="f05-01-9780702032318"', //ear: Anatomy of the canine ear.
	'/src="_REPLACE_LOCATION__21896818.jpg"/' => 'src="f05-02-9780702032318"', //ear: Calf double-tagged with large plastic identification tags in the ears.
	'/src="_REPLACE_LOCATION__21675096.jpg"/' => 'src="f05-03-9780702032318"', //ear: Ear tip necrosis in a calf associated with Salmonella dublin infection.
	'/src="_REPLACE_LOCATION__21675197.jpg"/' => 'src="f05-04-9780702032318"', //East Friesian, East Friesland: East Friesian or East Friesland dairy sheep.
	'/src="_REPLACE_LOCATION__21896821.jpg"/' => 'src="f05-05-9780702032318"', //ecchymosis: Ecchymoses.  From Bassert JM, McCurnin DM, McCurnin's Clinical Textbook for Veterinary Technicians, 7th ed, Saunders, 2010
	'/src="_REPLACE_LOCATION__21922948.jpg"/' => 'src="f05-06-9780702032318"', //ecdysis: A monitor lizard (Varanus spp.) undergoing ecdysis here sheds the outer layer of transparent skin that covers the tympanic membrane.
	'/src="_REPLACE_LOCATION__21924828.jpg"/' => 'src="f05-07-9780702032318"', //Echidnophaga: Adult Echidnophaga gallinacea, the stick-tight flea of poultry. A common flea of chickens and guinea fowl, it also feeds on dogs and cats.
	'/src="_REPLACE_LOCATION__21897840.jpg"/' => 'src="f05-08-9780702032318"', //Echinococcus: Echinococcus multilocularisin a rat.
	'/src="_REPLACE_LOCATION__21896828.jpg"/' => 'src="f05-09-9780702032318"', //ecthyma: Proliferative lesions of contagious ecthyma on the face of a Suffolk sheep. Suffolk sheep  have a particular propensity to develop proliferative, strawberry-like lesions, with this infection.
	'/src="_REPLACE_LOCATION__21922968.jpg"/' => 'src="f05-10-9780702032318"', //ectropion: Ectropion of the lower eyelid (and unrelated immature cataract) in an 8-year-old Saint Bernard.
	'/src="_REPLACE_LOCATION__21924848.jpg"/' => 'src="f05-11-9780702032318"', //eczema: An erythematous and eczematous skin reaction.
	'/src="_REPLACE_LOCATION__21896835.jpg"/' => 'src="f05-12-9780702032318"', //edema: Dependant edema in the subcutaneous tissues of the ventral abdomen and in the prepuce of a horse.
	'/src="_REPLACE_LOCATION__21896837.jpg"/' => 'src="f05-13-9780702032318"', //edema: Edema disease. Recumbent depressed pig showing edema of eyelid and forehead. Courtesy of JT Done.
	'/src="_REPLACE_LOCATION__21896839.jpg"/' => 'src="f05-14-9780702032318"', //edema: Pulmonary edema.
	'/src="_REPLACE_LOCATION__21896841.jpg"/' => 'src="f05-15-9780702032318"', //Ehlers–Danlos syndrome: Ehlers-Danlos syndrome in a 5-month-old Weimaraner showing the characteristic skin elasticity.
	'/src="_REPLACE_LOCATION__21922988.jpg"/' => 'src="f05-16-9780702032318"', //Ehmer sling: Ehmer sling.
	'/src="_REPLACE_LOCATION__.jpg"/' => 'src="f05-17-9780702032318"', //Eimeria: Unsporulated oocyst of Eimeria leuckarti.
	'/src="_REPLACE_LOCATION__21676433.jpg"/' => 'src="f05-18-9780702032318"', //elbow: Elbow joint of the dog.
	'/src="_REPLACE_LOCATION__21676527.jpg"/' => 'src="f05-19-9780702032318"', //electrocardiograph: Portable electrocardiograph used during exercise.
	'/src="_REPLACE_LOCATION__21676541.jpg"/' => 'src="f05-20-9780702032318"', //electrocardiography: Electrocardiography: Normal lead II ECG complex of the dog.
	'/src="_REPLACE_LOCATION__21676828.jpg"/' => 'src="f05-21-9780702032318"', //electrophoretogram: Cellulose acetate electrophoretogram.
	'/src="_REPLACE_LOCATION__21677028.jpg"/' => 'src="f05-22-9780702032318"', //Elizabethan collar: Elizabethan collar.
	//new image inserted. Missing image emasculatome
	'/src="_REPLACE_LOCATION__21924888.jpg"/' => 'src="f05-23-9780702032318"', //emasculatome: Burdizzo Emasculatome.
	'/src="_REPLACE_LOCATION__21896899.jpg"/' => 'src="f05-24-9780702032318"', //emphysema: Diffuse pulmonary emphysema in a cat.
	'/src="_REPLACE_LOCATION__21896804.jpg"/' => 'src="f05-25-9780702032318"', //enamel: Enamel hypoplasia.
	'/src="_REPLACE_LOCATION__21677552.jpg"/' => 'src="f05-26-9780702032318"', //enamel: Enamel spot.
	'/src="_REPLACE_LOCATION__21897842.jpg"/' => 'src="f05-27-9780702032318"', //encephalomyelitis: Horse with mosquito-borne Western equine encephalomyelitis (WEE) displaying the typical stance. Courtesy of CDC/Mr J Bagby.
	'/src="_REPLACE_LOCATION__21897743.jpg"/' => 'src="f05-28-9780702032318"', //encephalopathy:  Ewe with pregnancy toxemia encephalopathy, unaware of surroundings and not reactive to them, and with abnormal stance.
	'/src="_REPLACE_LOCATION__21896845.jpg"/' => 'src="f05-29-9780702032318"', //enchondromatosis: Multiple enchondromatosis in a 9-month-old Chihuahua.
	'/src="_REPLACE_LOCATION__21896847.jpg"/' => 'src="f05-30-9780702032318"', //endocardiosis: Endocardiosis of the left atrioventricular valve in a dog.
	'/src="_REPLACE_LOCATION__21896849.jpg"/' => 'src="f05-31-9780702032318"', //endocarditis: Vegetative and ulcerative endocarditis of the atrioventricular valve in a dog.
	'/src="_REPLACE_LOCATION__21678319.jpg"/' => 'src="f05-32-9780702032318"', //endoscope: Storz veterinary small animal endoscope.
	'/src="_REPLACE_LOCATION__21924911.jpg"/' => 'src="f05-33-9780702032318"', //endotracheal: Endotracheal tube type, material, and size comparison. A, Cuffed 11-mm, silicone rubber Murphy tube. B, 2.5-mm Cole tube. C, Cuffed 8-mm polyvinyl chloride (PVC) Murphy tube. D, Cuffed 4-mm red rubber Murphy tube. E, Uncuffed 2 mm PVC Murphy tube.
	'/src="_REPLACE_LOCATION__21923008.jpg"/' => 'src="f05-34-9780702032318"', //enophthalmos: Enophthalmos in both eyes resulting from emaciation.
	'/src="_REPLACE_LOCATION__21924928.jpg"/' => 'src="f05-35-9780702032318"', //Entameba: Motile feeding stage of Entamoeba histolytica, the trophozoite stage.
	'/src="_REPLACE_LOCATION__21896853.jpg"/' => 'src="f05-36-9780702032318"', //enterocyte: Tall columnar enterocytes lining the villus and being produced in the crypts in the small intestine.
	'/src="_REPLACE_LOCATION__21678984.jpg"/' => 'src="f05-37-9780702032318"', //enterolith: Enterolith from a horse.
	'/src="_REPLACE_LOCATION__21896855.jpg"/' => 'src="f05-38-9780702032318"', //enteropathy:  Thickened wall of the jejunum in porcine proliferative enteropathy.
	'/src="_REPLACE_LOCATION__21896857.jpg"/' => 'src="f05-39-9780702032318"', //enterotoxemia: Enterotoxemia (necrotic enteritis) caused by Clostridium perfringens type C. Courtesy of JT Done.
	'/src="_REPLACE_LOCATION__21896859.jpg"/' => 'src="f05-40-9780702032318"', //entropion: Entropion of lower eyelid margin in a foal.
	'/src="_REPLACE_LOCATION__21896861.jpg"/' => 'src="f05-41-9780702032318"', //entropion: Inherited congenital entropion in a Suffolk lamb involving the lower eyelid and resulting in conjunctivitis and purulent ocular discharge.
	'/src="_REPLACE_LOCATION__21897804.jpg"/' => 'src="f05-42-9780702032318"', //enzootic:  Leg of lamb that died from enzootic muscular dystrophy (white muscle disease) showing the characteristic change in muscle color that gives this disease this colloquial name.
	'/src="_REPLACE_LOCATION__21923028.jpg"/' => 'src="f05-43-9780702032318"', //eosinophil: Canine (C), feline (F), equine (E), and bovine (B) eosinophils demonstrating the variable size, shape, and color of granules in different species.
	'/src="_REPLACE_LOCATION__21896865.jpg"/' => 'src="f05-44-9780702032318"', //eosinophilic: Eosinophilic ulcer (bilateral) on the upper lip margins and an eosinophilic granuloma on the chin of a cat.
	'/src="_REPLACE_LOCATION__21896867.jpg"/' => 'src="f05-45-9780702032318"', //eosinophilic: Eosinophilic plaque on the abdomen of a cat.
	'/src="_REPLACE_LOCATION__21679548.jpg"/' => 'src="f05-46-9780702032318"', //eosinophilic: Bilateral eosinophilic ulcer in a cat.
	'/src="_REPLACE_LOCATION__21896869.jpg"/' => 'src="f05-47-9780702032318"', //Epicauta vittata: Epicauta vittata (blister beetle).
	'/src="_REPLACE_LOCATION__21896871.jpg"/' => 'src="f05-48-9780702032318"', //epidermal: Superficial pyoderma showing epidermal collarettes.
	'/src="_REPLACE_LOCATION__21923048.jpg"/' => 'src="f05-49-9780702032318"', //epididymis: Lateral view of the right canine testis and epididymis with the head (H), body (B), and tail (T) of the epididymis.
	'/src="_REPLACE_LOCATION__21924908.jpg"/' => 'src="f05-50-9780702032318"', //epiglottis: The anatomy of the pharynx and larynx: P, palate; T, tongue; E, epiglottis, which in this view is covering the glottis.
	'/src="_REPLACE_LOCATION__21896873.jpg"/' => 'src="f05-51-9780702032318"', //epiphora: Epiphora in a dog. Tears have stained the facial hairs brown.
	'/src="_REPLACE_LOCATION__21923068.jpg"/' => 'src="f05-52-9780702032318"', //epiphyseal: Epiphyseal (growth) plates. Radiograph of young cat pelvis and femurs. The epiphyseal plates appear dark because they are made up largely of cartilage, which is relatively transparent to x-rays.
	'/src="_REPLACE_LOCATION__21896875.jpg"/' => 'src="f05-53-9780702032318"', //epiphysitis:  Epiphysitis in a calf caused by copper deficiency.
	'/src="_REPLACE_LOCATION__21896877.jpg"/' => 'src="f05-54-9780702032318"', //episclera: Congestion of the episclera associated with glaucoma in a dog.
	'/src="_REPLACE_LOCATION__21680238.jpg"/' => 'src="f05-55-9780702032318"', //epistaxis: Epistaxis in a horse.
	'/src="_REPLACE_LOCATION__21896879.jpg"/' => 'src="f05-56-9780702032318"', //epithelium: Adaptive changes in epithelium.
	'/src="_REPLACE_LOCATION__21896881.jpg"/' => 'src="f05-57-9780702032318"', //epulis: Periodontal fibromatous epulis in a dog.
	'/src="_REPLACE_LOCATION__21680692.jpg"/' => 'src="f05-58-9780702032318"', //equine: Verrucose sarcoid in a horse.
	'/src="_REPLACE_LOCATION__21680718.jpg"/' => 'src="f05-59-9780702032318"', //equine: Conjunctivitis in equine viral arteritis.
	'/src="_REPLACE_LOCATION__21896883.jpg"/' => 'src="f05-60-9780702032318"', //ergot1: Peripheral necrosis, edema and hemorrhage caused by ergot alkaloids found present in feed pellets fed to this heifer.
	'/src="_REPLACE_LOCATION__21924948.jpg"/' => 'src="f05-61-9780702032318"', //ergot2: Ergot in horses.
	'/src="_REPLACE_LOCATION__21896885.jpg"/' => 'src="f05-62-9780702032318"', //erysipelas: Diamond (rhomboid) - shaped lesions on abdomen of a sow with erysipelas.
	'/src="_REPLACE_LOCATION__21896622.jpg"/' => 'src="f05-63-9780702032318"', //eschar: Full thickness burn with eschar in a dog caused by using a heating pad for post-operative warming.
	'/src="_REPLACE_LOCATION__21896887.jpg"/' => 'src="f05-64-9780702032318"', //esophagitis: Esophagitis.
	'/src="_REPLACE_LOCATION__21681589.jpg"/' => 'src="f05-65-9780702032318"', //esophagogram: Esophagogram of a cat with obstruction of the esophagus caused by a neoplasm.
	'/src="_REPLACE_LOCATION__21896889.jpg"/' => 'src="f05-66-9780702032318"', //estrus: Diagram of the estrus cycle and anestrus in the dog.
	'/src="_REPLACE_LOCATION__21896891.jpg"/' => 'src="f05-67-9780702032318"', //excoriation:  Pasture grass fire injury to a horse with burns of limbs and excoriation of burnt skin by tail movements.
	'/src="_REPLACE_LOCATION__21682926.jpg"/' => 'src="f05-68-9780702032318"', //Exmoor pony: Exmoor Pony.
	'/src="_REPLACE_LOCATION__21896893.jpg"/' => 'src="f05-69-9780702032318"', //exsanguination: Pigs that have died of exsanguinations as a result of hemorrhage from esophogastric ulcer. The loss of blood and resultant death in pig A was relatively rapid but in pig B was over many days and the pig has a characteristic runt conformation.
	'/src="_REPLACE_LOCATION__21683273.jpg"/' => 'src="f05-70-9780702032318"', //extensor: Extensor postural thrust response.
	'/src="_REPLACE_LOCATION__21896895.jpg"/' => 'src="f05-71-9780702032318"', //exudative:  Pigs with greasy pig disease (exudative epidermitis) showing the grease-like appearance of the seborrheic dermatitis that gives rise to its colloquial name.
	'/src="_REPLACE_LOCATION__21896897.jpg"/' => 'src="f05-72-9780702032318"', //eye: Sunken eye due to dehydration.


	'/src="_REPLACE_LOCATION__21923088.jpg"/' => 'src="f06-01-9780702032318"', //fabella: Distal displacement of the medial fabella (arrow) of the left stifle of a 2-year-old male West Highland White Terrier.
	'/src="_REPLACE_LOCATION__21896901.jpg"/' => 'src="f06-02-9780702032318"', //facial: Severe ulcerative facial dermatitis.
	'/src="_REPLACE_LOCATION__21896903.jpg"/' => 'src="f06-03-9780702032318"', //facial: Facial eczema. Skin sloughing because of damage from photosensitivity.
	'/src="_REPLACE_LOCATION__21683837.jpg"/' => 'src="f06-04-9780702032318"', //facial: Facial paralysis in a horse.
	'/src="_REPLACE_LOCATION__21684004.jpg"/' => 'src="f06-05-9780702032318"', //fading: Arabian fading syndrome.
	'/src="_REPLACE_LOCATION__21684090.jpg"/' => 'src="f06-06-9780702032318"', //Falabella: Falabella (Argentinian Dwarf Pony).
	'/src="_REPLACE_LOCATION__21924968.jpg"/' => 'src="f06-07-9780702032318"', //fascia: Micrograph of fascia surrounding muscle demonstrating a typical dense arrangement of collagen fibres where mechanical support is the primary function.
	'/src="_REPLACE_LOCATION__21923090.jpg"/' => 'src="f06-08-9780702032318"', //fasciitis: Necrotic skin lesion with underlying discoloration at elbow of dog with streptococcal necrotizing fasciitis.
	'/src="_REPLACE_LOCATION__21896907.jpg"/' => 'src="f06-09-9780702032318"', //Fasciola: Fasciola hepatica.
	'/src="_REPLACE_LOCATION__21684606.jpg"/' => 'src="f06-10-9780702032318"', //fat: Fatty liver in fat cow syndrome.
	'/src="_REPLACE_LOCATION__21923108.jpg"/' => 'src="f06-11-9780702032318"', //feather: Types of feathers. A, Contour. B, Semiplume. C, Down. D, Filoplume. E, Bristle.
	'/src="_REPLACE_LOCATION__21924988.jpg"/' => 'src="f06-12-9780702032318"', //feather: Contour feather. A, General structure. B, Microstructure of vane.
	'/src="_REPLACE_LOCATION__21684893.jpg"/' => 'src="f06-13-9780702032318"', //fecalith: Fecalith in a horse at necropsy.
	'/src="_REPLACE_LOCATION__21896917.jpg"/' => 'src="f06-14-9780702032318"', //feeding: Tube feeding a neonatal puppy.
	'/src="_REPLACE_LOCATION__21923128.jpg"/' => 'src="f06-15-9780702032318"', //feline: This blood smear from a domestic cat with feline infectious anemia shows a high proportion of the RBCs with single scattered or short chains of basophilic cocci or faint rings on the cell membrane that are characteristic of Mycoplasma haemofelis organisms.
	'/src="_REPLACE_LOCATION__21896919.jpg"/' => 'src="f06-16-9780702032318"', //feline: Feline infectious peritonitis showing fluid in the peritoneal cavity and fibrinous deposits on viscera.
	'/src="_REPLACE_LOCATION__21896921.jpg"/' => 'src="f06-17-9780702032318"', //femur: Femur of the dog.
	'/src="_REPLACE_LOCATION__21685429.jpg"/' => 'src="f06-18-9780702032318"', //fentanyl: Fentanyl patch.
	'/src="_REPLACE_LOCATION__21925008.jpg"/' => 'src="f06-19-9780702032318"', //Fergusson angiotribe: Ferguson Angiotribe Forceps:Curved.
	'/src="_REPLACE_LOCATION__21925011.jpg"/' => 'src="f06-20-9780702032318"', //ferret: Domestic ferret.
	'/src="_REPLACE_LOCATION__21685557.jpg"/' => 'src="f06-21-9780702032318"', //fertilization: Fertilization.
	//Missing 22a new image?
	'/src="_REPLACE_LOCATION__21896923.jpg"/' => 'src="f06-22a-9780702032318"', //fescue: Fescue foot in early stages with developing necrotic lower foot and showing line of demarcation of still vascularized tissue above with edema. The result of the vasoactive effects of ergot alkaloids that can be present in fescue grass and hay containing endophyte or in grains infected with ergot.
	'/src="_REPLACE_LOCATION__21896924.jpg"/' => 'src="f06-22b-9780702032318"', //fescue: Chronic fescue foot showing gangrene of lower foot with line of granulating tissue separating this from the more healthy tissue above
	'/src="_REPLACE_LOCATION__21685632.jpg"/' => 'src="f06-23-9780702032318"', //fetal: Calving jack (fetal extractor) for use in a cow.
	//f-24 image removed Feline fetal membranes. numbers off
	'/src="_REPLACE_LOCATION__21924028.jpg"/' => 'src="f06-24-9780702032318"', //fetotome: Fetotome.
	'/src="_REPLACE_LOCATION__21925013.jpg"/' => 'src="f06-25-9780702032318"', //fibrosarcoma: Maxillary fibrosarcoma.
	'/src="_REPLACE_LOCATION__21923148.jpg"/' => 'src="f06-26-9780702032318"', //filariasis: Dirofilaria immitis may be recovered from a variety of aberrant sites, such as the anterior chamber of the eye.
	'/src="_REPLACE_LOCATION__21686637.jpg"/' => 'src="f06-27-9780702032318"', //Filoviridae: Filoviridae. Colorized negative stained transmission electron micrograph of  Marburg virus virion. Courtesy of CDC/Frederick Murphy.
	'/src="_REPLACE_LOCATION__21687034.jpg"/' => 'src="f06-28-9780702032318"', //fistula: Esophageal fistula.
	'/src="_REPLACE_LOCATION__21687091.jpg"/' => 'src="f06-29-9780702032318"', //fistulous: Fistulous withers in a horse.
	'/src="_REPLACE_LOCATION__21687166.jpg"/' => 'src="f06-30-9780702032318"', //fixation: External fixation.
	'/src="_REPLACE_LOCATION__21687201.jpg"/' => 'src="f06-31-9780702032318"', //Fjord pony: Fjord pony.
	'/src="_REPLACE_LOCATION__21896927.jpg"/' => 'src="f06-32-9780702032318"', //flank: Flank watching in a horse, an indication of abdomonal pain.
	'/src="_REPLACE_LOCATION__21896929.jpg"/' => 'src="f06-33-9780702032318"', //flea: Flea allergy dermatitis.
	'/src="_REPLACE_LOCATION__21687643.jpg"/' => 'src="f06-34-9780702032318"', //flexural: Flexural deformity in a puppy.
	'/src="_REPLACE_LOCATION__21896931.jpg"/' => 'src="f06-35-9780702032318"', //flexural: Flexor laxity of the hindlimbs.
	'/src="_REPLACE_LOCATION__21687818.jpg"/' => 'src="f06-36-9780702032318"', //flow cytometry: A typical flow cytometer readout from labelling a cell population with antiequine CD4.
	'/src="_REPLACE_LOCATION__21896933.jpg"/' => 'src="f06-37-9780702032318"', //fluorescein: Fluorescin staining of a corneal ulcer associated with infectious keratoconjunctivitis in a Hereford heifer.
	'/src="_REPLACE_LOCATION__21896935.jpg"/' => 'src="f06-38-9780702032318"', //fly: Fly bite dermatitis on the ear tip of a dog.
	'/src="_REPLACE_LOCATION__21896937.jpg"/' => 'src="f06-39-9780702032318"', //foal: Foal heat diarrhea. Note soiling of the hindquarters.
	'/src="_REPLACE_LOCATION__21897119.jpg"/' => 'src="f06-40-9780702032318"', //foal: Lethal white foal showing colic.
	'/src="_REPLACE_LOCATION__21896939.jpg"/' => 'src="f06-41-9780702032318"', //fold1: Facial fold in an English bulldog.
	'/src="_REPLACE_LOCATION__21896941.jpg"/' => 'src="f06-42-9780702032318"', //follicular: Pigmentary clumping in hair shafts seen in black hair follicular dysplasia.
	'/src="_REPLACE_LOCATION__21896943.jpg"/' => 'src="f06-43-9780702032318"', //foot:  Foot abscess in a sheep.
	'/src="_REPLACE_LOCATION__21897755.jpg"/' => 'src="f06-44-9780702032318"', //foot: Footbath. Walk through foot bath for dairy cattle which is placed in a alley that the cows must at some time walk through. Used in control of footrot and of interdigital dermatitis.
	'/src="_REPLACE_LOCATION__21688929.jpg"/' => 'src="f06-45-9780702032318"', //footpad: Footpads on the dog.
	'/src="_REPLACE_LOCATION__21896951.jpg"/' => 'src="f06-46a-9780702032318"', //footrot:  Early ovine virulent footrot showing under-running (arrow) of the horn at the skin horn junction
	'/src="_REPLACE_LOCATION__21896953.jpg"/' => 'src="f06-46b-9780702032318"', //footrot:  Chronic ovine virulent footrot which is the carriage state for the disease. The soles of the digits are chalky in consistency and that on the left side has been pared away to expose the under-run sole and the site of persistent infection.
	'/src="_REPLACE_LOCATION__21925028.jpg"/' => 'src="f06-47-9780702032318"', //forestomachs: Stomach and forestomachs of cow. Location of reticulum, rumen, omasum, and abomasum as seen from right side of cow.
	'/src="_REPLACE_LOCATION__21689637.jpg"/' => 'src="f06-48-9780702032318"', //Fox terrier: Smooth fox terrier.
	'/src="_REPLACE_LOCATION__21896958.jpg"/' => 'src="f06-49-9780702032318"', //fracture: Types of fractures.
	'/src="_REPLACE_LOCATION__21896960.jpg"/' => 'src="f06-50-9780702032318"', //fracture: Comminuted fracture of the tibia in a dog.
	'/src="_REPLACE_LOCATION__21689749.jpg"/' => 'src="f06-51-9780702032318"', //fracture: Oblique fractures of the radius and ulna.
	'/src="_REPLACE_LOCATION__21689919.jpg"/' => 'src="f06-52-9780702032318"', //freemartin: Freemartin placenta.
	'/src="_REPLACE_LOCATION__21690053.jpg"/' => 'src="f06-53-9780702032318"', //frenulum: Lingual frenulum of the dog.
	'/src="_REPLACE_LOCATION__21690169.jpg"/' => 'src="f06-54-9780702032318"', //Friesian: Friesian dairy cow.
	'/src="_REPLACE_LOCATION__21690285.jpg"/' => 'src="f06-55-9780702032318"', //frostbite: Ear necrosis from frostbite.
	'/src="_REPLACE_LOCATION__21896964.jpg"/' => 'src="f06-56-9780702032318"', //fundus: Typical canine fundus.
	'/src="_REPLACE_LOCATION__21896966.jpg"/' => 'src="f06-57-9780702032318"', //fundus: Subalbinotic fundus of a dog.

	'/src="_REPLACE_LOCATION__21925048.jpg"/' => 'src="f07-01-9780702032318"', //gag: Mouth gag or speculum.
	'/src="_REPLACE_LOCATION__21896970.jpg"/' => 'src="f07-02-9780702032318"', //galactosemia: Cataract in a young kangaroo fed cow's milk.
	'/src="_REPLACE_LOCATION__21691377.jpg"/' => 'src="f07-03-9780702032318"', //Galloway: Galloway beef bull.
	'/src="_REPLACE_LOCATION__21896972.jpg"/' => 'src="f07-04-9780702032318"', //gangrene: Gangrene of the ear tips and snout in a pig with erysipelas.
	'/src="_REPLACE_LOCATION__21896974.jpg"/' => 'src="f07-05-9780702032318"', //gastric: Gastric dilatation-volvulus in a dog.
	'/src="_REPLACE_LOCATION__21896976.jpg"/' => 'src="f07-06-9780702032318"', //gastric: Gastric ulceration in the abomasum of a cow.
	'/src="_REPLACE_LOCATION__21693479.jpg"/' => 'src="f07-07-9780702032318"', //Giardia: Giemsa-stained fecal smear showing two Giardia trophozoites exhibiting the characteristic pear, or teardrop, shape with bilateral symmetry when viewed from the top, two nuclei, and fibrils running the length of the parasite.
	'/src="_REPLACE_LOCATION__21923168.jpg"/' => 'src="f07-08-9780702032318"', //Gigli wire saw: Gigli Wire Saw and Handles.
	'/src="_REPLACE_LOCATION__21693583.jpg"/' => 'src="f07-09-9780702032318"', //Gila monster: Gila monster.
	'/src="_REPLACE_LOCATION__21693597.jpg"/' => 'src="f07-10-9780702032318"', //gill: Gill system of fish.
	'/src="_REPLACE_LOCATION__21896978.jpg"/' => 'src="f07-11-9780702032318"', //gingivitis: Gingivitis in a dog.
	'/src="_REPLACE_LOCATION__21925068.jpg"/' => 'src="f07-12-9780702032318"', //gland: Uropygial gland of a barred owl (Strix varia).
	'/src="_REPLACE_LOCATION__21896980.jpg"/' => 'src="f07-13-9780702032318"', //Glässer's disease:  Postmortem of a pig with Glasser's disease showing serofibrinous pleuritis and peritonitis.
	'/src="_REPLACE_LOCATION__21925070.jpg"/' => 'src="f07-14-9780702032318"', //glossitis: Ulcerative glossitis, uremia, tongue, ventral surface, cat. Bilaterally symmetrical ulcers (arrows) are present on the rostrolateral borders of the ventral surface of the tongue.
	'/src="_REPLACE_LOCATION__21923188.jpg"/' => 'src="f07-15-9780702032318"', //glucose, d-glucose: Glucose tolerance curve.
	'/src="_REPLACE_LOCATION__21923190.jpg"/' => 'src="f07-16-9780702032318"', //goblet cell: Goblet Cell.
	'/src="_REPLACE_LOCATION__21923208.jpg"/' => 'src="f07-17-9780702032318"', //granulation: Exuberant granulation tissue on the metatarsus of a horse.
	'/src="_REPLACE_LOCATION__21925089.jpg"/' => 'src="f07-18-9780702032318"', //granuloma: Granuloma due to Blastomycosis.
	'/src="_REPLACE_LOCATION__21696429.jpg"/' => 'src="f07-19-9780702032318"', //granulosa cell tumor: Granulosa cell tumor in a mare's ovary atnecropsy.
	'/src="_REPLACE_LOCATION__21696689.jpg"/' => 'src="f07-20-9780702032318"', //Great Dane: Great Dane, harlequin.
	'/src="_REPLACE_LOCATION__21697416.jpg"/' => 'src="f07-21-9780702032318"', //guinea pig: Guinea pigs.
	'/src="_REPLACE_LOCATION__21697599.jpg"/' => 'src="f07-22-9780702032318"', //guttural pouch: Guttural pouch tympany.
	'/src="_REPLACE_LOCATION__21697673.jpg"/' => 'src="f07-23-9780702032318"', //gynecomastia: Gynecomastia of a buck goat.
	'/src="_REPLACE_LOCATION__21925128.jpg"/' => 'src="f08-01-9780702032318"', //Habronema: Sections of Habronema larvae (arrow) within nodule of granulomatous and eosinophilic dermatitis.
	'/src="_REPLACE_LOCATION__21697966.jpg"/' => 'src="f08-02-9780702032318"', //Haematopinus: Haematopinus asini.
	'/src="_REPLACE_LOCATION__21896992.jpg"/' => 'src="f08-03-9780702032318"', //haemonchosis: Haemonchus contortus infestation in the abomasum of a sheep.
	'/src="_REPLACE_LOCATION__21923228.jpg"/' => 'src="f08-04-9780702032318"', //Haemoproteus: Haemoproteus sp. in avian red blood cells.
	'/src="_REPLACE_LOCATION__21698124.jpg"/' => 'src="f08-05-9780702032318"', //Haflinger horse: Haflinger horse.
	'/src="_REPLACE_LOCATION__21698182.jpg"/' => 'src="f08-06-9780702032318"', //hair: Schematic diagram of the skin and simple and compound hair follicles.
	'/src="_REPLACE_LOCATION__21698309.jpg"/' => 'src="f08-07-9780702032318"', //half-hitch knot: Half-hitch knot.
	'/src="_REPLACE_LOCATION__21924008.jpg"/' => 'src="f08-08-9780702032318"', //Halsted: Halstead Mosquito Forceps.
	'/src="_REPLACE_LOCATION__21896994.jpg"/' => 'src="f08-09-9780702032318"', //Hampshire Down: Hampshire meat sheep.
	'/src="_REPLACE_LOCATION__21923248.jpg"/' => 'src="f08-10-9780702032318"', //haversian: Micrograph of a single Haversian system in Cortical (compact) bone.
	'/src="_REPLACE_LOCATION__21896998.jpg"/' => 'src="f08-11-9780702032318"', //head: Horse head pressing.
	'/src="_REPLACE_LOCATION__21699114.jpg"/' => 'src="f08-12-9780702032318"', //head: Head tilt.
	'/src="_REPLACE_LOCATION__21897000.jpg"/' => 'src="f08-13-9780702032318"', //headstock: Calf restrained in a headstock.
	'/src="_REPLACE_LOCATION__21897002.jpg"/' => 'src="f08-14-9780702032318"', //heart: Cardiac valves as viewed from the base of the heart.
	'/src="_REPLACE_LOCATION__21897004.jpg"/' => 'src="f08-15-9780702032318"', //heart sounds: Normal heart sounds.
	'/src="_REPLACE_LOCATION__21699323.jpg"/' => 'src="f08-16-9780702032318"', //heartworm: Heartworms (Dirofilaria immitis).
	'/src="_REPLACE_LOCATION__21699418.jpg"/' => 'src="f08-17-9780702032318"', //heaves: Heave line in a horse with chronic obstructive pulmonary disease.
	'/src="_REPLACE_LOCATION__21925148.jpg"/' => 'src="f08-18-9780702032318"', //heel: The anode heel effect. The x-ray beam intensity decreases toward the anode side because of absorption by the target and anode material.
	'/src="_REPLACE_LOCATION__21897007.jpg"/' => 'src="f08-19-9780702032318"', //hemal: Lymphosarcoma involving superficial hemal lymph nodes.
	'/src="_REPLACE_LOCATION__21923268.jpg"/' => 'src="f08-20-9780702032318"', //hemangioma: A focal vascular proliferation typical of Hemangioma.
	'/src="_REPLACE_LOCATION__21897009.jpg"/' => 'src="f08-21-9780702032318"', //hemangiopericytoma: Hemangiopericytoma.
	'/src="_REPLACE_LOCATION__21699816.jpg"/' => 'src="f08-22-9780702032318"', //hemangiosarcoma: Hemangiosarcoma in liver.
	'/src="_REPLACE_LOCATION__21897011.jpg"/' => 'src="f08-23-9780702032318"', //hematoma: Hematoma in the wall of the vulva of a mare.
	'/src="_REPLACE_LOCATION__21897013.jpg"/' => 'src="f08-24-9780702032318"', //hematuria: Hematuria in a cow with pyelonephritis on left compared with normal urine on right.
	'/src="_REPLACE_LOCATION__21700169.jpg"/' => 'src="f08-25-9780702032318"', //hemicerclage: Hemicerclage wire.
	'/src="_REPLACE_LOCATION__21925169.jpg"/' => 'src="f08-26-9780702032318"', //hemipenes: Saline solution injection has everted the hemipenes.
	'/src="_REPLACE_LOCATION__21700375.jpg"/' => 'src="f08-27-9780702032318"', //hemiwalking: Hemiwalking.
	'/src="_REPLACE_LOCATION__21897015.jpg"/' => 'src="f08-28-9780702032318"', //hemoglobinuric: Hemmoglobinuric nephrosis.
	'/src="_REPLACE_LOCATION__21923908.jpg"/' => 'src="f08-29-9780702032318"', //hemolysis: Alpha-hemolysis produced by some strains of Streptococcus.
	'/src="_REPLACE_LOCATION__21701127.jpg"/' => 'src="f08-30-9780702032318"', //hepatic: Hepatic fibrosis with nodular hyperplasia.
	'/src="_REPLACE_LOCATION__21925188.jpg"/' => 'src="f08-31-9780702032318"', //Hepatozoon: Hepatozoon americanum gamont (arrow) within a canine leukocyte. Bowman, Dwight D.. Georgis' Parasitology for Veterinarians, 9th Edition. Saunders, 2008.
	'/src="_REPLACE_LOCATION__21896552.jpg"/' => 'src="f08-32-9780702032318"', //hereditary: Loose hyperextensible skin over the hip area, typical of hereditary equine regional dermal asthenia (HERDA).
	'/src="_REPLACE_LOCATION__21701616.jpg"/' => 'src="f08-33-9780702032318"', //Hereford: Hereford beef bull.
	'/src="_REPLACE_LOCATION__21925208.jpg"/' => 'src="f08-34-9780702032318"', //hernia: Umbilical hernia in a pig.
	'/src="_REPLACE_LOCATION__21701728.jpg"/' => 'src="f08-35-9780702032318"', //hernia: Scrotal hernia in a horse.
	'/src="_REPLACE_LOCATION__21925228.jpg"/' => 'src="f08-36-9780702032318"', //Heterobilharzia: Thin-shelled egg of Heterobilharzia americanum.
	'/src="_REPLACE_LOCATION__21897021.jpg"/' => 'src="f08-37-9780702032318"', //heterophil: Avian red blood cells and a heterophil.
	'/src="_REPLACE_LOCATION__21923888.jpg"/' => 'src="f08-38-9780702032318"', //Hexamita: Hexamita species.
	'/src="_REPLACE_LOCATION__21702490.jpg"/' => 'src="f08-39-9780702032318"', //Highland: Highland beef cattle.
	'/src="_REPLACE_LOCATION__21897023.jpg"/' => 'src="f08-40-9780702032318"', //Himalayan: Himalayan cat.
	'/src="_REPLACE_LOCATION__21897025.jpg"/' => 'src="f08-41-9780702032318"', //hippomanes: Hippomanes from two dfferent mares. The color difference is not significant.
	'/src="_REPLACE_LOCATION__21702682.jpg"/' => 'src="f08-42-9780702032318"', //hirsutism, hirsuties: Hirsutism in a horse with pituitary adenoma.
	'/src="_REPLACE_LOCATION__21702757.jpg"/' => 'src="f08-43-9780702032318"', //histiocytoma: Histiocytoma.
	'/src="_REPLACE_LOCATION__21923868.jpg"/' => 'src="f08-44-9780702032318"', //histoplasmosis: Tissue aspirate showing a macrophage filled with numerous Histoplasma capsulatum organisms as well as many found loose in the background.
	'/src="_REPLACE_LOCATION__21703082.jpg"/' => 'src="f08-45-9780702032318"', //hog1: Pig restrained with a hog holder.
	'/src="_REPLACE_LOCATION__21703249.jpg"/' => 'src="f08-46-9780702032318"', //Holter monitoring: Holter monitor on a dog.
	'/src="_REPLACE_LOCATION__21897844.jpg"/' => 'src="f08-47-9780702032318"', //honey bee: Apis mellifera (European honey bee). Courtesy of CDC/Dr Pratt.
	'/src="_REPLACE_LOCATION__21897027.jpg"/' => 'src="f08-48-9780702032318"', //hoof: Structures of Equine Hoof A, Left forefoot, standing, lateral view. B, Forefoot, ventral view.
	'/src="_REPLACE_LOCATION__21897029.jpg"/' => 'src="f08-49-9780702032318"', //hoof:  Hoof block applied to the claw of a cow.
	'/src="_REPLACE_LOCATION__21897031.jpg"/' => 'src="f08-50-9780702032318"', //hoof: Hoof testers.
	'/src="_REPLACE_LOCATION__21703606.jpg"/' => 'src="f08-51-9780702032318"', //hopping: Hopping response.
	'/src="_REPLACE_LOCATION__21703818.jpg"/' => 'src="f08-52-9780702032318"', //Horner's syndrome: Horner's syndrome.
	'/src="_REPLACE_LOCATION__21897035.jpg"/' => 'src="f08-53-9780702032318"', //horseshoe: Bar horseshoe.
	'/src="_REPLACE_LOCATION__21897037.jpg"/' => 'src="f08-54-9780702032318"', //housing: Loose housing, on this farm in dry lots.
	'/src="_REPLACE_LOCATION__21704242.jpg"/' => 'src="f08-55-9780702032318"', //humerus: Canine left humerus. Lateral view.
	'/src="_REPLACE_LOCATION__21897039.jpg"/' => 'src="f08-56a-9780702032318"', //hutch: Calf hutches are moveable to “new ground” and allow the individual housing, both important in reducing risk of disease. They provide shelter and warmth in the winter and can be opened for heat dissipation in the summer.
	'/src="_REPLACE_LOCATION__21897040.jpg"/' => 'src="f08-56b-9780702032318"', //hutch: Opaque calf hutch.
	'/src="_REPLACE_LOCATION__21897043.jpg"/' => 'src="f08-57-9780702032318"', //hyaloid: Persistent hyaloid artery in a dog.
	'/src="_REPLACE_LOCATION__21897045.jpg"/' => 'src="f08-58-9780702032318"', //hydrallantois: Mare with hydrallantois, showing the enlarged abdomen and the distension of the ventral abdomen.
	'/src="_REPLACE_LOCATION__21897047.jpg"/' => 'src="f08-59a-9780702032318"', //hydrocephalus: Hydrocephalus in a calf.
	'/src="_REPLACE_LOCATION__21704681.jpg"/' => 'src="f08-59b-9780702032318"', //hydrocephalus: Radiograph of a hydrocephalic Chihuahua puppy showing the domed skull with thinning of the cranial bones and open fontanelle.
	'/src="_REPLACE_LOCATION__21897049.jpg"/' => 'src="f08-60-9780702032318"', //hydronephrosis: Hydronephrosis in a dog.
	'/src="_REPLACE_LOCATION__21897051.jpg"/' => 'src="f08-61-9780702032318"', //hydropericardium: Hydropericardium in a cat.
	'/src="_REPLACE_LOCATION__21705191.jpg"/' => 'src="f08-62-9780702032318"', //hygroma: Carpal hygroma.
	'/src="_REPLACE_LOCATION__21896626.jpg"/' => 'src="f08-63-9780702032318"', //hygroma: Hock hygroma (capped hock) in a horse.
	'/src="_REPLACE_LOCATION__21897053.jpg"/' => 'src="f08-64-9780702032318"', //hyperemia: Hyperemia of oral mucous membranes in a horse.  From van Dijk JE, Gruys E, Mouwen JMVM, Color Atlas of Veterinary Pathology, 2nd ed, Saunders, 2007
	'/src="_REPLACE_LOCATION__21897055.jpg"/' => 'src="f08-65-9780702032318"', //hyperkeratosis:  Hyperkeratosis of the skin in a sow with a chronic sarcoptic mange.
	'/src="_REPLACE_LOCATION__21705893.jpg"/' => 'src="f08-66-9780702032318"', //hyperkeratosis:  Nasal hyperkeratosis in a dog.
	'/src="_REPLACE_LOCATION__21897057.jpg"/' => 'src="f08-67-9780702032318"', //hypervitaminosis: Radiograph showing confluent exostosis of cervical vertebrae of a cat caused by hypervitaminosis A.
	'/src="_REPLACE_LOCATION__21706515.jpg"/' => 'src="f08-68-9780702032318"', //hyphema: Hyphema.
	'/src="_REPLACE_LOCATION__21897059.jpg"/' => 'src="f08-69-9780702032318"', //hypocalcemia: Frog-leg position characteristic of ewes with hypocalcemia.
	'/src="_REPLACE_LOCATION__21706991.jpg"/' => 'src="f08-70-9780702032318"', //hypokalemic: Cervical ventroflexion in a cat with hypokalemic polymyopathy.
	'/src="_REPLACE_LOCATION__21707254.jpg"/' => 'src="f08-71-9780702032318"', //hypopyon: Hypopyon in a calf.
	'/src="_REPLACE_LOCATION__21897772.jpg"/' => 'src="f08-72a-9780702032318"', //hypospadia: Hypospadias of the glans penis in a stallion. The urethral orifice is slit-like and only partially surrounded by the fossa glandis and glans penis.
	'/src="_REPLACE_LOCATION__21897773.jpg"/' => 'src="f08-72b-9780702032318"', //hypospadia: Perineal hypospadia in a calf.
	'/src="_REPLACE_LOCATION__21707823.jpg"/' => 'src="f09-01-9780702032318"', //Icelandic horse, pony: Icelandic pony.
	'/src="_REPLACE_LOCATION__21897068.jpg"/' => 'src="f09-02-9780702032318"', //immunity: General dynamics and characteristics of the primary and secondary antibody responses.
	'/src="_REPLACE_LOCATION__21897070.jpg"/' => 'src="f09-03-9780702032318"', //immunoglobulin: Structure of an immunoglobulin molecule.
	'/src="_REPLACE_LOCATION__21897072.jpg"/' => 'src="f09-04-9780702032318"', //infectious: Infectious bovine keratoconjunctivitis (pink eye) in a heifer.
	//new image, missing image infraorbital
	'/src="_REPLACE_LOCATION__21925108.jpg"/' => 'src="f09-05-9780702032318"', //infraorbital: Location for blocking the infraorbital foramen in the dog. The infraorbital foramina are the sites for injection to provide nerve block to the entire maxilla.
	'/src="_REPLACE_LOCATION__21897074.jpg"/' => 'src="f09-06-9780702032318"', //injection-site reactions: Necrosis at the site of a previous injection with copper glycinate.
	'/src="_REPLACE_LOCATION__21897076.jpg"/' => 'src="f09-07-9780702032318"', //interdigital: Interdigital fibroma (corn) between the toes of a bull's foot.
	'/src="_REPLACE_LOCATION__21897078.jpg"/' => 'src="f09-08-9780702032318"', //intermandibular: Intermandibular swelling and lymphadenitis in cow with actinobacillosis (wooden tongue).
	'/src="_REPLACE_LOCATION__21897891.jpg"/' => 'src="f09-09-9780702032318"', //intradermal: Single intradermal test for tuberculosis in cattle. Injection of tuberculin intradermally in a caudal fold of the tail.
	'/src="_REPLACE_LOCATION__21897080.jpg"/' => 'src="f09-10-9780702032318"', //intussusception: Distended and congested loops of intestine in a cow with intussusception.
	'/src="_REPLACE_LOCATION__21712707.jpg"/' => 'src="f09-11-9780702032318"', //iris1: Iris bombé.
	'/src="_REPLACE_LOCATION__21712750.jpg"/' => 'src="f09-12-9780702032318"', //Irish water spaniel: Irish water spaniel.
	'/src="_REPLACE_LOCATION__21713379.jpg"/' => 'src="f09-13-9780702032318"', //Isospora: Sporulated Isospora felisoocysts.
	'/src="_REPLACE_LOCATION__21922488.jpg"/' => 'src="f09-14-9780702032318"', //Ixodes: Six-legged Ixodeslarva.

	'/src="_REPLACE_LOCATION__21713794.jpg"/' => 'src="f10-01-9780702032318"', //Jacob sheep:  Jacob sheep.
	'/src="_REPLACE_LOCATION__21922449.jpg"/' => 'src="f10-02-9780702032318"', //Jamshidi biopsy needle: Jamshidi bone marrow biopsy needle.
	'/src="_REPLACE_LOCATION__21897083.jpg"/' => 'src="f10-03-9780702032318"', //jaundice: Jaundice in a horse's oral mucosa.
	'/src="_REPLACE_LOCATION__21714141.jpg"/' => 'src="f10-04-9780702032318"', //Jersey: Jersey dairy cow.
	'/src="_REPLACE_LOCATION__21714243.jpg"/' => 'src="f10-05-9780702032318"', //Johne's disease: Thick, transverse rugae in the ileum of a cow with Johne's disease (right) compared with normal ileum (left).
	'/src="_REPLACE_LOCATION__21897085.jpg"/' => 'src="f10-06-9780702032318"', //joint: Tendon and joint laxity in a premature foal.
	'/src="_REPLACE_LOCATION__21897089.jpg"/' => 'src="f10-07-9780702032318"', //joint-ill:  Joint-ill.
	'/src="_REPLACE_LOCATION__21897093.jpg"/' => 'src="f10-08-9780702032318"', //jugular: A three-month-old calf with swelling of the ventral neck due to a hematoma. The left jugular vein is markedly engorged.
	'/src="_REPLACE_LOCATION__21897091.jpg"/' => 'src="f10-09-9780702032318"', //jugular: External jugular vein in the dog.
	'/src="_REPLACE_LOCATION__21714793.jpg"/' => 'src="f11-01-9780702032318"', //Karakul: Karakul sheep.
	'/src="_REPLACE_LOCATION__21897095.jpg"/' => 'src="f11-02-9780702032318"', //kemps: Kemp hair in the fleece of a young lamb with border disease (hairy shaker disease).
	'/src="_REPLACE_LOCATION__21897097.jpg"/' => 'src="f11-03-9780702032318"', //keratitis: Chronic superficial keratitis in a German shepherd dog.
	'/src="_REPLACE_LOCATION__21923848.jpg"/' => 'src="f11-04-9780702032318"', //keratocyte: A keratocyte, exhibiting what appears to be a ruptured "vesicle" in blood from a cat with hepatic lipidosis.
	'/src="_REPLACE_LOCATION__21897099.jpg"/' => 'src="f11-05-9780702032318"', //kerion: Dermatophytosis showing the intense inflammatory reaction typical of a kerion.
	'/src="_REPLACE_LOCATION__21715507.jpg"/' => 'src="f11-06a-9780702032318"', //kidney: Canine kidney.
	'/src="_REPLACE_LOCATION__21897101.jpg"/' => 'src="f11-06b-9780702032318"', //kidney: Bovine kidney.
	'/src="_REPLACE_LOCATION__21897674.jpg"/' => 'src="f11-07-9780702032318"', //kidney: Turkey egg kidney.
	'/src="_REPLACE_LOCATION__21923988.jpg"/' => 'src="f11-08-9780702032318"', //Kirby-Bauer method: Kirby-Bauer plate, illustrating the disk diffusion antimicrobial susceptibility test method.
	'/src="_REPLACE_LOCATION__21897103.jpg"/' => 'src="f11-09-9780702032318"', //kissing calves: Kissing calves.
	'/src="_REPLACE_LOCATION__21716085.jpg"/' => 'src="f11-10-9780702032318"', //knuckling: Knuckling over.
	'/src="_REPLACE_LOCATION__21716650.jpg"/' => 'src="f12-01-9780702032318"', //lacrimal: Nasolacrimal apparatus in the dog.
	'/src="_REPLACE_LOCATION__21897107.jpg"/' => 'src="f12-02-9780702032318"', //lactation: Premature lactation in a mare with ascending placentitis.
	'/src="_REPLACE_LOCATION__21717353.jpg"/' => 'src="f12-03-9780702032318"', //laminitis: Typical rings and abnormal hoof growth of chronic laminitis in a horse.
	'/src="_REPLACE_LOCATION__21717432.jpg"/' => 'src="f12-04-9780702032318"', //Landrace: Landrace pig.
	'/src="_REPLACE_LOCATION__21925248.jpg"/' => 'src="f12-05-9780702032318"', //Lane forceps: Lane forceps.
	'/src="_REPLACE_LOCATION__21717495.jpg"/' => 'src="f12-06-9780702032318"', //Lantana: Lantana camara.
	'/src="_REPLACE_LOCATION__21717799.jpg"/' => 'src="f12-07-9780702032318"', //laryngoscope: Laryngoscope.
	'/src="_REPLACE_LOCATION__21897846.jpg"/' => 'src="f12-08-9780702032318"', //Latrodectus: Female black widow spider (Latrodectus mactans). Courtesy of CDC/Paula Smith; Photo: James Gathany.
	'/src="_REPLACE_LOCATION__21923828.jpg"/' => 'src="f12-09-9780702032318"', //lavage: Percutaneous technique for transtracheal wash and bronchoalveolar lavage.
	'/src="_REPLACE_LOCATION__21897111.jpg"/' => 'src="f12-10-9780702032318"', //lavage: Subpalpebral lavage system in a horse.
	'/src="_REPLACE_LOCATION__21897113.jpg"/' => 'src="f12-11-9780702032318"', //Legg–Calvé–Perthes disease: Radiograph of Legg-Calve-Perthés disease in an 8-month-old Pekingese.
	'/src="_REPLACE_LOCATION__21897115.jpg"/' => 'src="f12-12-9780702032318"', //leiomyoma: Pedunculated leiomyoma in the vagina of a dog.
	'/src="_REPLACE_LOCATION__21923808.jpg"/' => 'src="f12-13-9780702032318"', //Leishmania: Macrophage from bone marrow of an infected dog containing large numbers of Leishmania infantum amastigotes.
	'/src="_REPLACE_LOCATION__21718626.jpg"/' => 'src="f12-14-9780702032318"', //lens: Lens luxation in a horse's eye.
	'/src="_REPLACE_LOCATION__21897117.jpg"/' => 'src="f12-15-9780702032318"', //leprosy: A solitary lesion on the face of a cat with feline leprosy.
	'/src="_REPLACE_LOCATION__21923788.jpg"/' => 'src="f12-16-9780702032318"', //Leucocytozoon: Leucocytozoon sp in a blood smear from a red-tailed hawk.
	'/src="_REPLACE_LOCATION__21923768.jpg"/' => 'src="f12-17-9780702032318"', //leukemia: Peripheral blood smear from a dog with chronic lymphocytic leukemia showing increased number of mature lymphocytes.
	'/src="_REPLACE_LOCATION__21897121.jpg"/' => 'src="f12-18-9780702032318"', //leukoma:  Leukoma.
	'/src="_REPLACE_LOCATION__21897123.jpg"/' => 'src="f12-19-9780702032318"', //lichenification: Lichenification.
	'/src="_REPLACE_LOCATION__21719974.jpg"/' => 'src="f12-20-9780702032318"', //Limousin: Limousin beef bull.
	'/src="_REPLACE_LOCATION__21720206.jpg"/' => 'src="f12-21-9780702032318"', //Linognathus: Linognathus vituli.
	'/src="_REPLACE_LOCATION__21720367.jpg"/' => 'src="f12-22-9780702032318"', //Lipitsa: Lipitsa or Lipizzaner horse.
	'/src="_REPLACE_LOCATION__21897125.jpg"/' => 'src="f12-23-9780702032318"', //lipofuscinosis: Bovine renal lipofuscinosis.
	'/src="_REPLACE_LOCATION__21925268.jpg"/' => 'src="f12-24-9780702032318"', //lipoma: Lipoma on the forearm of an aged dog.
	'/src="_REPLACE_LOCATION__21925271.jpg"/' => 'src="f12-25-9780702032318"', //liposarcoma: Aspirate from a liposarcoma. Note the vacuolated cytoplasm with indistinct borders, large nucleus, ropy chromatin pattern, and multiple prominent nucleoli.
	'/src="_REPLACE_LOCATION__21923748.jpg"/' => 'src="f12-26-9780702032318"', //Lister scissors: Lister bandage scissors.
	'/src="_REPLACE_LOCATION__21897127.jpg"/' => 'src="f12-27-9780702032318"', //listeriosis:  Merino ewe with listerial encephalitis.
	'/src="_REPLACE_LOCATION__21925288.jpg"/' => 'src="f12-28-9780702032318"', //Littauer scissors: Littauer scissors.
	'/src="_REPLACE_LOCATION__21897129.jpg"/' => 'src="f12-29-9780702032318"', //liver: Liver abscess from rumenitis in a feedlot steer.
	'/src="_REPLACE_LOCATION__21925308.jpg"/' => 'src="f12-30a-9780702032318"', //lock ups: Lock up. Cow about to enter lock up.
	'/src="_REPLACE_LOCATION__21925310.jpg"/' => 'src="f12-30b-9780702032318"', //lock ups: Lock up. Cow caught in closed lock up.
	'/src="_REPLACE_LOCATION__21721471.jpg"/' => 'src="f12-31-9780702032318"', //lordosis: Lordosis in a horse.
	'/src="_REPLACE_LOCATION__21897139.jpg"/' => 'src="f12-32-9780702032318"', //lung: Lung lobes.
	'/src="_REPLACE_LOCATION__21721897.jpg"/' => 'src="f12-33-9780702032318"', //lungeing: Lungeing.
	'/src="_REPLACE_LOCATION__21897141.jpg"/' => 'src="f12-34-9780702032318"', //Lupinus:  Cleft palate in a calf resulting from exposure to teratogenic lupine alkaloids in early fetal life.
	'/src="_REPLACE_LOCATION__21897144.jpg"/' => 'src="f12-35-9780702032318"', //Lupinus: Velvet lupine (Lupinus leucophyllus).
	'/src="_REPLACE_LOCATION__21897146.jpg"/' => 'src="f12-36-9780702032318"', //lupus erythematosus (LE): Discoid lupus erythematosus in a Collie.
	'/src="_REPLACE_LOCATION__21722286.jpg"/' => 'src="f12-37-9780702032318"', //lymphangitis: Ulcerative lymphangitis.
	'/src="_REPLACE_LOCATION__21722326.jpg"/' => 'src="f12-38-9780702032318"', //lymphatic: Lymphatic vessels and lymph nodes in the dog.
	'/src="_REPLACE_LOCATION__21897152.jpg"/' => 'src="f12-39-9780702032318"', //lymphedema: Swelling, hyperkeratosis and skin ulcerations characteristic of chronic progressive lymphedema in four affected Belgian draft horses.
	'/src="_REPLACE_LOCATION__21925328.jpg"/' => 'src="f12-40-9780702032318"', //lymphocyte: A small, mature lymphocyte in blood from a normal canine.
	'/src="_REPLACE_LOCATION__21897154.jpg"/' => 'src="f12-41-9780702032318"', //lymphoma: Juvenile lymphoma in the thymus of a calf.
	'/src="_REPLACE_LOCATION__21897156.jpg"/' => 'src="f12-42-9780702032318"', //lymphosarcoma: Marked enlargement of the cervicdal and prescapular lymph nodes in a Great Dane with lymphosarcoma.
	'/src="_REPLACE_LOCATION__21723054.jpg"/' => 'src="f13-01-9780702032318"', //Macracanthorhynchus: Macracanthorhynchus ingensproboscis.
	'/src="_REPLACE_LOCATION__21923730.jpg"/' => 'src="f13-02-9780702032318"', //macrocyte: Macrocytes (long arrows) and acanthocytes (small arrow) in canine blood film.
	'/src="_REPLACE_LOCATION__21923728.jpg"/' => 'src="f13-03-9780702032318"', //macrophage: A macrophage showing erythrophagocytosis.
	'/src="_REPLACE_LOCATION__21897158.jpg"/' => 'src="f13-04-9780702032318"', //magnet: Nail and wire covering a reticular magnet recovered at postmortem of a cow.
	'/src="_REPLACE_LOCATION__21923708.jpg"/' => 'src="f13-05-9780702032318"', //malachite green: Malachite green endospore stain of Bacillus anthracis.
	'/src="_REPLACE_LOCATION__21723685.jpg"/' => 'src="f13-06-9780702032318"', //Malassezia: Malassezia pachydermatisfrom a dog ear (modified Wright-Giemsa stain).
	'/src="_REPLACE_LOCATION__21897160.jpg"/' => 'src="f13-07-9780702032318"', //malasseziasis: Malasseziasis in a West Highland white terrier.
	'/src="_REPLACE_LOCATION__21897162.jpg"/' => 'src="f13-08-9780702032318"', //malignant catarrhal fever (MCF): Malignant catarrhal fever, keratoconjunctivitis showing centripetal spread of corneal opacity.
	'/src="_REPLACE_LOCATION__21925348.jpg"/' => 'src="f13-09-9780702032318"', //Mallophaga: Mallophaga (Trichodectes canis) of the dog: male on left, female on right.
	'/src="_REPLACE_LOCATION__21723956.jpg"/' => 'src="f13-10-9780702032318"', //malunion: Malunion of a distal femoral fracture in a dog.
	'/src="_REPLACE_LOCATION__21897164.jpg"/' => 'src="f13-11-9780702032318"', //mammary: Fibroepithelial mammary hyperplasia in a young cat.
	'/src="_REPLACE_LOCATION__21724044.jpg"/' => 'src="f13-12-9780702032318"', //mammary: Mammary gland.
	'/src="_REPLACE_LOCATION__21724219.jpg"/' => 'src="f13-13-9780702032318"', //mandible: Lateral view of the dog mandible.
	'/src="_REPLACE_LOCATION__21925388.jpg"/' => 'src="f13-14-9780702032318"', //Mannheimia: Mannheimia haemolyticacolonies on blood agar.
	'/src="_REPLACE_LOCATION__21897168.jpg"/' => 'src="f13-15-9780702032318"', //Manx Loaghtan: Manx Loaghtan.
	'/src="_REPLACE_LOCATION__21897170.jpg"/' => 'src="f13-16-9780702032318"', //mask: Face mask for supplemental oxygen.
	'/src="_REPLACE_LOCATION__21925408.jpg"/' => 'src="f13-17-9780702032318"', //Mason-meta splint: Mason-meta splint.
	'/src="_REPLACE_LOCATION__21923688.jpg"/' => 'src="f13-18-9780702032318"', //mast cell: Mast cell tumor. A, Leg mass. Dog. Note the large, raised, haired nodule on the lateral stifle area that resembles grossly a lipoma.
	'/src="_REPLACE_LOCATION__21897174.jpg"/' => 'src="f13-19-9780702032318"', //mastitis: Enlargement of the mammary gland in a mare with mastitis.
	'/src="_REPLACE_LOCATION__21897172.jpg"/' => 'src="f13-20-9780702032318"', //mastitis: Acute necrotizing mastitis.
	'/src="_REPLACE_LOCATION__21897176.jpg"/' => 'src="f13-21-9780702032318"', //meconium: Meconium may be present as discrete balls or have a pasty consistency, as shown.
	'/src="_REPLACE_LOCATION__21897178.jpg"/' => 'src="f13-22-9780702032318"', //mediastinal:  Abscessation of the mediastinal lymph nodes in a sheep with caseous lymphadenitis.
	'/src="_REPLACE_LOCATION__21725976.jpg"/' => 'src="f13-23-9780702032318"', //megacolon: Barium enema in an aged cat with idiopathic megacolon.
	'/src="_REPLACE_LOCATION__21725990.jpg"/' => 'src="f13-24a-9780702032318"', //megaesophagus: Radiograph of megaesophagus in a dog.
	'/src="_REPLACE_LOCATION__21897180.jpg"/' => 'src="f13-24b-9780702032318"', //megaesophagus: Post mortem specimen of megaesophagus.
	'/src="_REPLACE_LOCATION__21923668.jpg"/' => 'src="f13-25-9780702032318"', //megakaryocyte: Bone marrow aspirate. Mature megakaryocyte (left) with a condensed multilobulated nucleus and eosinophilic, granular cytoplasm. A multinucleated osteoclast is on the right.
	'/src="_REPLACE_LOCATION__21897184.jpg"/' => 'src="f13-26-9780702032318"', //meibomian: Meibomian adenoma on the upper eyelid of a dog.
	'/src="_REPLACE_LOCATION__21897186.jpg"/' => 'src="f13-27-9780702032318"', //meibomitis: Meibomitis or marginal blepharitis in a dog.
	'/src="_REPLACE_LOCATION__21897188.jpg"/' => 'src="f13-28-9780702032318"', //melanoma: Pony with melanomas in perianal region.
	'/src="_REPLACE_LOCATION__21897190.jpg"/' => 'src="f13-29-9780702032318"', //melanoma:  Malignant melanoma in the oral cavity of a dog.
	'/src="_REPLACE_LOCATION__21726708.jpg"/' => 'src="f13-30-9780702032318"', //meningocele: Meningocele in a calf.
	'/src="_REPLACE_LOCATION__21897192.jpg"/' => 'src="f13-31-9780702032318"', //Merino: Merino sheep.
	'/src="_REPLACE_LOCATION__21897194.jpg"/' => 'src="f13-32-9780702032318"', //metastasis: Metastases from a splenic hemangiosarcoma implanted on the omentum.
	'/src="_REPLACE_LOCATION__21897198.jpg"/' => 'src="f13-33-9780702032318"', //Michel: Michel skin clips and Michel clip forceps.
	'/src="_REPLACE_LOCATION__21897200.jpg"/' => 'src="f13-34-9780702032318"', //microfilaria: Canine blood smear with microfilaria of Dirofilaria immitis.
	'/src="_REPLACE_LOCATION__21897202.jpg"/' => 'src="f13-35-9780702032318"', //microphthalmos: Microphthalmia in a Collie.
	'/src="_REPLACE_LOCATION__21897205.jpg"/' => 'src="f13-36-9780702032318"', //Microsporum: Microsporum.
	'/src="_REPLACE_LOCATION__21897207.jpg"/' => 'src="f13-37-9780702032318"', //Microsporum: Microsporum nanum infection in a pig.
	'/src="_REPLACE_LOCATION__21897209.jpg"/' => 'src="f13-38-9780702032318"', //milking: Milking parlor on a dairy farm.
	'/src="_REPLACE_LOCATION__21729890.jpg"/' => 'src="f13-39-9780702032318"', //mimosine: Loss of tail hairs due to poisoning by the toxin mimosine in Leucaena leucocephala.
	'/src="_REPLACE_LOCATION__21897211.jpg"/' => 'src="f13-40-9780702032318"', //mosquito: Mosquito bite hypersensitivity on the nose of a cat.
	'/src="_REPLACE_LOCATION__21897219.jpg"/' => 'src="f13-41-9780702032318"', //mulberry heart disease: Mulberry heart disease.
	'/src="_REPLACE_LOCATION__21897221.jpg"/' => 'src="f13-42-9780702032318"', //mummification: Fetal mummification.
	'/src="_REPLACE_LOCATION__21732692.jpg"/' => 'src="f13-43-9780702032318"', //Muscovy: Muscovy ducks.
	'/src="_REPLACE_LOCATION__21897223.jpg"/' => 'src="f13-44-9780702032318"', //muscular dystrophy: Muscular dystrophy. Recently shorn sheep showing painful stance.
	'/src="_REPLACE_LOCATION__21897225.jpg"/' => 'src="f13-45-9780702032318"', //muzzle: Gauze used to make a muzzle on a dog.
	'/src="_REPLACE_LOCATION__21733101.jpg"/' => 'src="f13-46-9780702032318"', //mycobacterial: Opportunist mycobacterial granuloma in a dog.
	'/src="_REPLACE_LOCATION__21897227.jpg"/' => 'src="f13-47-9780702032318"', //mycotic:  Mycotic dermatitis in a Hereford.
	'/src="_REPLACE_LOCATION__21897231.jpg"/' => 'src="f13-48-9780702032318"', //myiasis: Myiasis. Numerous maggots filling a cutaneous lesion in an adult dog.
	'/src="_REPLACE_LOCATION__21897233.jpg"/' => 'src="f13-49-9780702032318"', //myocardial:  Myocardial dystrophy.
	'/src="_REPLACE_LOCATION__21897217.jpg"/' => 'src="f13-50-9780702032318"', //myoclonus: Extensor hindlimb rigidity in a recumbent calf with inherited congenital myoclonus (neuraxial edema).
	'/src="_REPLACE_LOCATION__21733978.jpg"/' => 'src="f13-51-9780702032318"', //myoglobinuria: Myoglobinuria in a horse with exertional myopathy.
	'/src="_REPLACE_LOCATION__21897235.jpg"/' => 'src="f14-01-9780702032318"', //nail: Nail trimmers, White (left), Resco (right).
	'/src="_REPLACE_LOCATION__21734941.jpg"/' => 'src="f14-02-9780702032318"', //nasal: Nasal deviation in a horse.
	'/src="_REPLACE_LOCATION__21897237.jpg"/' => 'src="f14-03-9780702032318"', //nasal: Nasal discharge of mucous and milk in a calf with a cleft palate.
	'/src="_REPLACE_LOCATION__21897241.jpg"/' => 'src="f14-04-9780702032318"', //necrobacillosis: Necrobacillosis.
	'/src="_REPLACE_LOCATION__21897243.jpg"/' => 'src="f14-05-9780702032318"', //necrotic:  Necrotic enteritis.
	'/src="_REPLACE_LOCATION__21897245.jpg"/' => 'src="f14-06-9780702032318"', //needle: Needle holders. Mayo-Hegar (left), Olsen-Hegar (right).
	'/src="_REPLACE_LOCATION__21897247.jpg"/' => 'src="f14-07-9780702032318"', //nipple: Inverted nipples.
	'/src="_REPLACE_LOCATION__21737933.jpg"/' => 'src="f14-08-9780702032318"', //nit: Nit (louse egg) cemented to hair.
	'/src="_REPLACE_LOCATION__21923648.jpg"/' => 'src="f14-09-9780702032318"', //node: A nerve impulse jumps from one node of Ranvier to the next, producing rapid conduction of the nerve impulse, From Colville TP, Bassert JM, Clinical Anatomy and Physiology for Veterinary Technicians, 2nd Edition. Mosby, 2008.
	'/src="_REPLACE_LOCATION__21738666.jpg"/' => 'src="f14-10-9780702032318"', //Normande cattle: Normande dual-purpose cows.
	'/src="_REPLACE_LOCATION__21738821.jpg"/' => 'src="f14-11-9780702032318"', //nose: Anatomic structures of the canine nose.
	'/src="_REPLACE_LOCATION__21897251.jpg"/' => 'src="f14-12-9780702032318"', //Notoedres: Notoedres cati mite.
	'/src="_REPLACE_LOCATION__21897253.jpg"/' => 'src="f14-13-9780702032318"', //notoedric mange: Notoedres cati eggs and fecal pellets in a skin scraping from a cat.
	'/src="_REPLACE_LOCATION__21923632.jpg"/' => 'src="f14-14-9780702032318"', //nucleated: Nucleated red blood cells in a smear from a dog.
	'/src="_REPLACE_LOCATION__21739924.jpg"/' => 'src="f15-01-9780702032318"', //obturator: Obturator paralysis.
	'/src="_REPLACE_LOCATION__21897257.jpg"/' => 'src="f15-02a-9780702032318"', //occlusion: Class II malocclusion in a dog.
	'/src="_REPLACE_LOCATION__21897258.jpg"/' => 'src="f15-02b-9780702032318"', //occlusion: Class III malocclusion in a dog.
	'/src="_REPLACE_LOCATION__21924148.jpg"/' => 'src="f15-03-9780702032318"', //odd-eyed cat: Odd-eyed white cat.
	'/src="_REPLACE_LOCATION__21923968.jpg"/' => 'src="f15-04-9780702032318"', //odontoma: Small abnormal supernumerary teeth associated with the mandibular molars typical of a compound odontoma.
	'/src="_REPLACE_LOCATION__21923630.jpg"/' => 'src="f15-05-9780702032318"', //Oesophagostomum: Oesophagostomum dentatum, the "nodular worm" of swine, induce formation of large nodules within the wall of the large intestine.
	'/src="_REPLACE_LOCATION__21925428.jpg"/' => 'src="f15-06-9780702032318"', //Olsen-Hegar needle holder: Olson-Hegar Needle Holder–Scissors Combination.
	'/src="_REPLACE_LOCATION__21897239.jpg"/' => 'src="f15-07-9780702032318"', //omphalitis: Omphalitis (navel ill). Swelling of the external umbilicus with purulent exudates.
	'/src="_REPLACE_LOCATION__21897745.jpg"/' => 'src="f15-08-9780702032318"', //onchocercosis: Onchocercosis in the eye of a horse.
	'/src="_REPLACE_LOCATION__21923628.jpg"/' => 'src="f15-09-9780702032318"', //onychodystrophy: Lupoid onychodystrophy in a dog. Numerous dystrophic nails growing in abnormal directions.
	'/src="_REPLACE_LOCATION__21741352.jpg"/' => 'src="f15-10-9780702032318"', //onychomycosis: Onychomycosis.
	'/src="_REPLACE_LOCATION__21897263.jpg"/' => 'src="f15-11-9780702032318"', //ophthalmia: Contagious ophthalmia (pink eye) in a sheep.
	'/src="_REPLACE_LOCATION__21742462.jpg"/' => 'src="f15-12-9780702032318"', //Orlov trotter: Orlov trotter horse.
	'/src="_REPLACE_LOCATION__21897265.jpg"/' => 'src="f15-13-9780702032318"', //Oslerus: Multifocal proliferative tracheitis caused by Oslerus osleri.
	'/src="_REPLACE_LOCATION__21743027.jpg"/' => 'src="f15-14-9780702032318"', //osmosis: Osmosis.
	'/src="_REPLACE_LOCATION__21897267.jpg"/' => 'src="f15-15-9780702032318"', //ossification: Dorsopalmar radiographs of ther carpal joints demonstrating incomplete ossification with absence of many of the cuboidal bones and the proximal epiphysis of the metacarpal bone.
	'/src="_REPLACE_LOCATION__21897269.jpg"/' => 'src="f15-16-9780702032318"', //osteitis: Osteitis deformans in a Burmese python (Python molurus bivittatus).
	'/src="_REPLACE_LOCATION__21897271.jpg"/' => 'src="f15-17-9780702032318"', //osteoarthritis: Marked swelling of the tarsal joint in a foal with septic osteoarthritis. Joint swelling and key features of septic osteoarthritis.  From McAuliffe SB, Slovis NM, Color Atlas of Diseases and Disorders of the Foal, Saunders, 2008
	'/src="_REPLACE_LOCATION__21897273.jpg"/' => 'src="f15-18-9780702032318"', //osteodystrophy: Hypertrophic osteodystrophy in a 4-month-old Great Dane.
	'/src="_REPLACE_LOCATION__21897777.jpg"/' => 'src="f15-19a-9780702032318"', //osteogenesis: Newborn lamb with osteogenesis imperfecta. Note the domed head and brachygnathia inferior.
	'/src="_REPLACE_LOCATION__21897778.jpg"/' => 'src="f15-19b-9780702032318"', //osteogenesis: Enucleated eye from a normal lamb (left) and a lamb with osteogenesis imperfecta. The sclera from the affected lamb is dark blue.
	'/src="_REPLACE_LOCATION__21897279.jpg"/' => 'src="f15-20-9780702032318"', //osteomyelitis: Suppurative osteomyelitis.  From van Dijk JE, Gruys E, Mouwen JMVM, Color Atlas of Veterinary Pathology, 2nd ed, Saunders, 2007
	'/src="_REPLACE_LOCATION__21897281.jpg"/' => 'src="f15-21-9780702032318"', //osteopathy: Hypertrophic osteopathy in the front limb of a dog with a primary lung tumor.
	'/src="_REPLACE_LOCATION__21897283.jpg"/' => 'src="f15-22-9780702032318"', //osteoporosis: Lamb with osteoporosis due to calcium deficiency as a result of grain diet during drought-feeding.
	'/src="_REPLACE_LOCATION__21897285.jpg"/' => 'src="f15-23-9780702032318"', //osteosarcoma: Osteosarcoma of the proximal humerus in a 3-year-old English setter.
	'/src="_REPLACE_LOCATION__21897288.jpg"/' => 'src="f15-24-9780702032318"', //ostertagiasis: Ostertagiasis. Abomasum of a steer.
	'/src="_REPLACE_LOCATION__21897291.jpg"/' => 'src="f15-25-9780702032318"', //otitis: Chronic otitis externa in a dog.
	'/src="_REPLACE_LOCATION__21743773.jpg"/' => 'src="f15-26-9780702032318"', //Otodectes: Otodectes cynotis.
	'/src="_REPLACE_LOCATION__21744102.jpg"/' => 'src="f15-27-9780702032318"', //ovary: Ovary of a sow with mature follicles.
	'/src="_REPLACE_LOCATION__21897293.jpg"/' => 'src="f15-28-9780702032318"', //overgrown: Overgrown claw in a cow ('scissor claw').
	'/src="_REPLACE_LOCATION__21897295.jpg"/' => 'src="f15-29-9780702032318"', //overo: Overo mare with lethal white foal.  From McAuliffe SB, Slovis NM, Color Atlas of Diseases and Disorders of the Foal, Saunders, 2008
	'/src="_REPLACE_LOCATION__21744475.jpg"/' => 'src="f15-30-9780702032318"', //owl: Tawny owl.
	'/src="_REPLACE_LOCATION__21744866.jpg"/' => 'src="f15-31-9780702032318"', //oxyuriasis: Tail-rubbing caused by Oxyuris equiinfestation.
	'/src="_REPLACE_LOCATION__21897297.jpg"/' => 'src="f15-32-9780702032318"', //Oxyuris: Oxyurid egg (center) and oocysts of Isospora spp.  From Pasmans F, Blahak S, Martel A, Pantchev N: Introducing reptiles into a captive collection: The role of the veterinarian. Vet J 175:53-68, 2008. Elsevier.
	'/src="_REPLACE_LOCATION__21745266.jpg"/' => 'src="f16-01-9780702032318"', //paint horse: Paint horse.
	'/src="_REPLACE_LOCATION__21923570.jpg"/' => 'src="f16-02-9780702032318"', //palpebral: Assessing the palpebral reflex by lightly tapping the medial or lateral canthus.
	'/src="_REPLACE_LOCATION__21923588.jpg"/' => 'src="f16-03-9780702032318"', //pancreatic: Anatomy of pancreatic islets.
	'/src="_REPLACE_LOCATION__21923608.jpg"/' => 'src="f16-04-9780702032318"', //pannus: Synovitis and arthritis of hock joints of swine with pannus extending across the articular surface.
	'/src="_REPLACE_LOCATION__21897301.jpg"/' => 'src="f16-05-9780702032318"', //papilla: Incisive papilla in a dog.
	'/src="_REPLACE_LOCATION__21746183.jpg"/' => 'src="f16-06-9780702032318"', //papilloma: Papilloma on a horse's muzzle.
	'/src="_REPLACE_LOCATION__21897303.jpg"/' => 'src="f16-07-9780702032318"', //papilloma: Skin tag (fibrovascular papilloma).
	'/src="_REPLACE_LOCATION__21746200.jpg"/' => 'src="f16-08-9780702032318"', //papillomatosis: Canine viral papillomatosis.
	'/src="_REPLACE_LOCATION__21896968.jpg"/' => 'src="f16-09-9780702032318"', //papillomatosis: Fibropapillomas on a cow's teat.
	'/src="_REPLACE_LOCATION__21897307.jpg"/' => 'src="f16-10-9780702032318"', //papular: Papular stomatitis.
	'/src="_REPLACE_LOCATION__21746556.jpg"/' => 'src="f16-11-9780702032318"', //Paragonimus: Ova of Paragonimus kellicotti.
	'/src="_REPLACE_LOCATION__21746859.jpg"/' => 'src="f16-12-9780702032318"', //paranasal: Paranasal sinuses of the dog.
	'/src="_REPLACE_LOCATION__21897309.jpg"/' => 'src="f16-13-9780702032318"', //paraphimosis: Paraphimosis.
	'/src="_REPLACE_LOCATION__21925448.jpg"/' => 'src="f16-14-9780702032318"', //Parascaris: Egg ofParascaris equorum.
	'/src="_REPLACE_LOCATION__21897311.jpg"/' => 'src="f16-15-9780702032318"', //paresis: Spastic paresis.
	'/src="_REPLACE_LOCATION__21923548.jpg"/' => 'src="f16-16-9780702032318"', //paronychia: Paronychia in a cat caused by Microsporum canis.From Peterson M, Small Animal Pediatrics: The First 12 Months of Life. Saunders, 2010.
	'/src="_REPLACE_LOCATION__21925488.jpg"/' => 'src="f16-17-9780702032318"', //Pasteurella: Pasteurella multocidacultivated on blood agar.
	'/src="_REPLACE_LOCATION__21925491.jpg"/' => 'src="f16-18-9780702032318"', //patella: Radiograph of the skyline view of the patella.
/*ERROR starts to get out of order 19 and 20 renamed on s3*/
	'/src="_REPLACE_LOCATION__21897319.jpg"/' => 'src="f16-19-9780702032318"', //patellar: Bilateral congenital patellar luxation.
	'/src="_REPLACE_LOCATION__21747929.jpg"/' => 'src="f16-20-9780702032318"', //patellar: Testing the patellar reflex.
	'/src="_REPLACE_LOCATION__21897321.jpg"/' => 'src="f16-21-9780702032318"', //patent: Patent ductus arteriosus (PDA) in a dog.
	'/src="_REPLACE_LOCATION__21923528.jpg"/' => 'src="f16-22-9780702032318"', //pectus: Lateral thoracic radiograph of a cat with severe pectus excavatum.
	'/src="_REPLACE_LOCATION__21748451.jpg"/' => 'src="f16-23-9780702032318"', //pedal: Septic pedal interphalangeal arthritis.
	'/src="_REPLACE_LOCATION__21923510.jpg"/' => 'src="f16-24-9780702032318"', //Pelger-Huët anomaly: Nuclear hyposegmentation in neutrophil (N) and eosinophil (E) from a dog with Pelger-Huët anomaly.
/*ERROR new image for #25 throws off numbering */
	'/src="_REPLACE_LOCATION__21925508.jpg"/' => 'src="f16-25-9780702032318"', //Pelodera: PeloderaDermatitis.
	'/src="_REPLACE_LOCATION__21897781.jpg"/' => 'src="f16-26-9780702032318"', //pemphigus: Pemphigus foliaceus in a dog showing crusting erosive dermatitis on the nasal planum with depigmentation and loss of the normal cobblestone texture.
	'/src="_REPLACE_LOCATION__21897782.jpg"/' => 'src="f16-27-9780702032318"', //pemphigus: Hyperkeratosis of the footpads in a dog with pemphigus foliaceous.
	'/src="_REPLACE_LOCATION__21897327.jpg"/' => 'src="f16-28-9780702032318"', //pemphigus: Erosive dermatitis on the footpads of a dog with pemphigus vulgaris.
	'/src="_REPLACE_LOCATION__21923508.jpg"/' => 'src="f16-29-9780702032318"', //peptic: Peptic ulcer of the duodenum.
	'/src="_REPLACE_LOCATION__21749316.jpg"/' => 'src="f16-30-9780702032318"', //Percheron: Percheron horse.
	'/src="_REPLACE_LOCATION__21897335.jpg"/' => 'src="f16-31-9780702032318"', //perianal: Perianal fistulae. Severe ulcerative dermatitis of the entire perianal region with numerous deep fistulae.
	'/src="_REPLACE_LOCATION__21925528.jpg"/' => 'src="f16-32-9780702032318"', //pericardiocentesis: Pericardiocentesis in a dog.
	'/src="_REPLACE_LOCATION__21897337.jpg"/' => 'src="f16-33-9780702032318"', //pericarditis:  Purulent traumatic pericarditis.
	'/src="_REPLACE_LOCATION__21897339.jpg"/' => 'src="f16-34-9780702032318"', //perineal: First degree perineal laceration showing tearing of the dorsal commissure of the vulva.
	'/src="_REPLACE_LOCATION__21923488.jpg"/' => 'src="f16-35-9780702032318"', //periodontal: A periodontal probe used to evaluate the depth of the periodontal pocket.
	'/src="_REPLACE_LOCATION__21925548.jpg"/' => 'src="f16-36-9780702032318"', //periosteal: Periosteal Elevator.
	'/src="_REPLACE_LOCATION__21750292.jpg"/' => 'src="f16-37-9780702032318"', //peristalsis: Peristalsis.
	'/src="_REPLACE_LOCATION__21897341.jpg"/' => 'src="f16-38-9780702032318"', //peromelia: Peromelia in a newborn lamb. Both hind limbs and the left forelimb are affected. The right forelimb is normal.
/*ERROR starts to get out of order with #25 renamed on s3*/
	'/src="_REPLACE_LOCATION__21925568.jpg"/' => 'src="f16-39-9780702032318"', //Persian: Persian cat.
	'/src="_REPLACE_LOCATION__21751126.jpg"/' => 'src="f16-40-9780702032318"', //phagocytosis: Phagocytosis.
/* ERROR_no_figure_number_found new image below throws off numbering */
	'/src="_REPLACE_LOCATION__21897343.jpg"/' => 'src="f16-41-9780702032318"', //phenotype: Polypay lamb with the phenotype of Border disease (hairy shaker disease).
	'/src="_REPLACE_LOCATION__21897345.jpg"/' => 'src="f16-42a-9780702032318"', //photosensitization: Hereford with severe dermatitis from photosensitivity.
	'/src="_REPLACE_LOCATION__21897346.jpg"/' => 'src="f16-42b-9780702032318"', //photosensitization: Photosensitive dermatitis on the teat of a cow.
	'/src="_REPLACE_LOCATION__21752553.jpg"/' => 'src="f16-43-9780702032318"', //phycomycosis: Fungating phycomycosis lesions on the chest of a horse.
	'/src="_REPLACE_LOCATION__21897349.jpg"/' => 'src="f16-44-9780702032318"', //Pietrain: Pietrain pig.
	'/src="_REPLACE_LOCATION__21897351.jpg"/' => 'src="f16-45-9780702032318"', //pig: Pot-bellied pig.
	'/src="_REPLACE_LOCATION__21753553.jpg"/' => 'src="f16-46-9780702032318"', //pinocytosis: Mechanism of pinocytosis.
	'/src="_REPLACE_LOCATION__21753578.jpg"/' => 'src="f16-47-9780702032318"', //pinto: Pinto horse.
	'/src="_REPLACE_LOCATION__21753595.jpg"/' => 'src="f16-48-9780702032318"', //Pinzgau: Pinzgau dual-purpose cow.
	'/src="_REPLACE_LOCATION__21897354.jpg"/' => 'src="f16-49-9780702032318"', //pityriasis: Pityriasis rosea.
	'/src="_REPLACE_LOCATION__21753935.jpg"/' => 'src="f16-50-9780702032318"', //placenta: Cotyledonary placenta of ruminants.
	'/src="_REPLACE_LOCATION__21897356.jpg"/' => 'src="f16-51-9780702032318"', //placenta: Retained fetal membranes.
	'/src="_REPLACE_LOCATION__21897358.jpg"/' => 'src="f16-52-9780702032318"', //placenta: Zonary placenta of carnivores.
	'/src="_REPLACE_LOCATION__21897360.jpg"/' => 'src="f16-53-9780702032318"', //placentitis: Ascending placentitis viewed from the allantoic surface, note the thickening and discoloration around the cervical star.
	'/src="_REPLACE_LOCATION__21925588.jpg"/' => 'src="f16-54-9780702032318"', //plantigrade: Plantigrade posture in a cat with diabetes mellitus and exocrine pancreatic insufficiency.
	'/src="_REPLACE_LOCATION__21754550.jpg"/' => 'src="f16-55-9780702032318"', //plastron: Plastron of the tortoise.
	/*s3numbering of image below out of order. s3 renumbered*/
	'/src="_REPLACE_LOCATION__21897362.jpg"/' => 'src="f16-56-9780702032318"', //plate: Metacarpal-phalangeal radiograph demonstrating trandphyseal bridging with a "plate and screw" technique.
	'/src="_REPLACE_LOCATION__21897364.jpg"/' => 'src="f16-57-9780702032318"', //pleural: Lateral thoracic radiograph of a dog with a pleural effusion. Increased density is seen within the thoracic cavity, obscuring the cardiac silhouette.
	'/src="_REPLACE_LOCATION__21754850.jpg"/' => 'src="f16-58-9780702032318"', //pleuritis, pleurisy: Fibrinous pleuritis in a horse at necropsy.
	'/src="_REPLACE_LOCATION__21755575.jpg"/' => 'src="f16-59-9780702032318"', //pneumopericardiography: Pneumopericardiogram in a dog.
	'/src="_REPLACE_LOCATION__21755644.jpg"/' => 'src="f16-60-9780702032318"', //pneumothorax: Pneumothorax.
	'/src="_REPLACE_LOCATION__21897366.jpg"/' => 'src="f16-61-9780702032318"', //pododemodicosis: Pododemodecosis.
	'/src="_REPLACE_LOCATION__21754321.jpg"/' => 'src="f16-62-9780702032318"', //pododermatitis: Plasma cell pododermatitis in a cat.
	'/src="_REPLACE_LOCATION__21756183.jpg"/' => 'src="f16-63-9780702032318"', //Poitou ass: Poitou ass.
	'/src="_REPLACE_LOCATION__21756351.jpg"/' => 'src="f16-64-9780702032318"', //poll: Poll evil in horse.
	'/src="_REPLACE_LOCATION__21756652.jpg"/' => 'src="f16-65-9780702032318"', //polydactylism: Polydactylism with the extra digit located at the level of the metacarpo-phalangeal joint.
	'/src="_REPLACE_LOCATION__21897375.jpg"/' => 'src="f16-66-9780702032318"', //posture: Praying posture in a dog with acute abdominal pain.
	'/src="_REPLACE_LOCATION__21897850.jpg"/' => 'src="f16-67-9780702032318"', //prairie dog: Prairie dog. Courtesy of CDC/Susy Mercado.
	'/src="_REPLACE_LOCATION__21897380.jpg"/' => 'src="f16-68-9780702032318"', //pregnancy:  Ewe with drought-induced pregnancy toxemia. The ewe is separated from other sheep in the flock, she can hear the approach of a person but cannot see and usually can be easily caught.
	/*Error new image below throws off #ing */
	'/src="_REPLACE_LOCATION__21897382.jpg"/' => 'src="f16-69-9780702032318"', //premature: "Premature heartbeats. Portion of non-consecutive trans-thoracic lead and base-apex lead (upper and lower tracings, respectively) ECG tracings obtained during 24-h Holter monitoring in a horse: (A) Junctional premature complex (third complex); (B) interpolated ventricular premature complex (third complex) and (C) short run of ventricular tachycardia with QRS-T morphology (asterisks) different from that seen in (B). Paper speed 25 mm/s; 5 mm = 1 mV.
	'/src="_REPLACE_LOCATION__21897384.jpg"/' => 'src="f16-70-9780702032318"', //prepubic: Mare with rupture of the prepubic tendon.  From McAuliffe SB, Slovis NM, Color Atlas of Diseases and Disorders of the Foal, Saunders, 2008
	'/src="_REPLACE_LOCATION__21759255.jpg"/' => 'src="f16-71-9780702032318"', //preputial: Partial preputial eversion in a bull.
	'/src="_REPLACE_LOCATION__21897386.jpg"/' => 'src="f16-72-9780702032318"', //pressure: Pressure necrosis. Pressure sore over the lateral hock. Bassert JM, McCurnin DM, McCurnin's Clinical Textbook for Veterinary Technicians, 7th ed, Saunders, 2010
	'/src="_REPLACE_LOCATION__21760433.jpg"/' => 'src="f16-73a-9780702032318"', //prolapse: Uterine prolapse in a mare.
	'/src="_REPLACE_LOCATION__21897388.jpg"/' => 'src="f16-73b-9780702032318"', //prolapse: Third degree uterine prolapse in a postpartum cow with exposure of the caruncles.
	'/src="_REPLACE_LOCATION__21897390.jpg"/' => 'src="f16-74a-9780702032318"', //prolapse:  Vaginal fold prolapse in a bitch.
	'/src="_REPLACE_LOCATION__21897392.jpg"/' => 'src="f16-74b-9780702032318"', //prolapse: Vaginal prolapse in a cow.
	'/src="_REPLACE_LOCATION__21761641.jpg"/' => 'src="f16-75-9780702032318"', //Przewalski's horse: Przewalski's horse.
	'/src="_REPLACE_LOCATION__21897394.jpg"/' => 'src="f16-76-9780702032318"', //psittacine: Psittacine beak and feather disease in a sulfur crested cockatoo.
	'/src="_REPLACE_LOCATION__21897396.jpg"/' => 'src="f16-77-9780702032318"', //Psoroptes: Psoroptes cuniculimites.
	'/src="_REPLACE_LOCATION__21897398.jpg"/' => 'src="f16-78-9780702032318"', //psoroptic mange: Psoroptic manged in the ear of a rabbit showing the typical heavy encrustations.
	'/src="_REPLACE_LOCATION__21762453.jpg"/' => 'src="f16-79-9780702032318"', //Pteridium: Pteridium aquilinum.
	'/src="_REPLACE_LOCATION__21762758.jpg"/' => 'src="f16-80-9780702032318"', //Puli: Puli.
	'/src="_REPLACE_LOCATION__21897400.jpg"/' => 'src="f16-81-9780702032318"', //pulmonary: "Intrapulmonary hemorrhage attributable to erosion of the pulmonary artery by a lung abscess in a cow with caudal vena caval thrombosis. Immediately adjacent to this, purulent material is seen exiting a lung abscess that has been cut open.
	'/src="_REPLACE_LOCATION__21897402.jpg"/' => 'src="f16-82-9780702032318"', //pulmonic: Pulmonic stenosis in a dog.
	'/src="_REPLACE_LOCATION__21897404.jpg"/' => 'src="f16-83-9780702032318"', //pulpitis: Pulpitis of the left mandibular canine tooth.
	'/src="_REPLACE_LOCATION__21897409.jpg"/' => 'src="f16-84-9780702032318"', //pupillary: Iris-iris persistent pupillary membrane in a dog.
	'/src="_REPLACE_LOCATION__21897411.jpg"/' => 'src="f16-85a-9780702032318"', //purpura: Purpura hemorrhagica, side view.
	'/src="_REPLACE_LOCATION__21897412.jpg"/' => 'src="f16-85b-9780702032318"', //purpura: Purpura hemorrhagica, front view.
	'/src="_REPLACE_LOCATION__21897415.jpg"/' => 'src="f16-86-9780702032318"', //purpura: Thrombocytopenic purpura in a piglet.
	'/src="_REPLACE_LOCATION__21897419.jpg"/' => 'src="f16-87-9780702032318"', //pyoderma: Chin pyoderma in a dog.
	'/src="_REPLACE_LOCATION__21897421.jpg"/' => 'src="f16-88-9780702032318"', //pyoderma: Juvenile pyoderma in a German shorthaired pointer puppy.
	'/src="_REPLACE_LOCATION__21897423.jpg"/' => 'src="f16-89-9780702032318"', //pyoderma: Mucocutaneous pyoderma.
	'/src="_REPLACE_LOCATION__21897425.jpg"/' => 'src="f16-90-9780702032318"', //pyoderma: Nasal pyoderma.
	'/src="_REPLACE_LOCATION__21897427.jpg"/' => 'src="f16-91-9780702032318"', //pyometra: Pyometra in a dog.
	'/src="_REPLACE_LOCATION__21925608.jpg"/' => 'src="f16-92-9780702032318"', //pythiosis: Gastric pythiosis in a dog. Pythium insidiosumappears as unstained, elongated structures surrounded by inflammatory cells.
/*Error end renumbering for f16, letter 9*/

	'/src="_REPLACE_LOCATION__21923948.jpg"/' => 'src="f17-01-9780702032318"', //QRS complex, QRS wave: Close-up of a normal feline lead II P-QRS-T complex.
	'/src="_REPLACE_LOCATION__21897432.jpg"/' => 'src="f17-02a-9780702032318"', //quarter: Early quarter crack.
	'/src="_REPLACE_LOCATION__21764553.jpg"/' => 'src="f17-02b-9780702032318"', //quarter: Quarter crack.
	'/src="_REPLACE_LOCATION__21897429.jpg"/' => 'src="f17-03-9780702032318"', //Quarter horse: Quarter horse.
	'/src="_REPLACE_LOCATION__21764638.jpg"/' => 'src="f17-04-9780702032318"', //Queensland itch: Queensland itch lesions on a horse's skin.
	'/src="_REPLACE_LOCATION__21897434.jpg"/' => 'src="f17-05-9780702032318"', //quicklime: Tail, vulval and hock necrosis in a piglet caused by burns from quicklime used to disinfect the pen floor.
	'/src="_REPLACE_LOCATION__21764776.jpg"/' => 'src="f17-06-9780702032318"', //quittor: Quittor in a horse.
	'/src="_REPLACE_LOCATION__21897852.jpg"/' => 'src="f18-01-9780702032318"', //raccoon: Raccoon. (Procyon lotor). Courtesy of CDC.
	'/src="_REPLACE_LOCATION__21765075.jpg"/' => 'src="f18-02-9780702032318"', //radial: Radial agenesis in a kitten.
	'/src="_REPLACE_LOCATION__21897436.jpg"/' => 'src="f18-03-9780702032318"', //Raillietia: Raillietia aurisfrom the ear of a cow.
	'/src="_REPLACE_LOCATION__21897854.jpg"/' => 'src="f18-04-9780702032318"', //rattlesnake: Timber rattlesnake (Crotalus horridus). Courtesy of CEC/Edward J. Wozniak.
	'/src="_REPLACE_LOCATION__21897440.jpg"/' => 'src="f18-05-9780702032318"', //rectal: Rectal stricture in a pig.
	'/src="_REPLACE_LOCATION__21897442.jpg"/' => 'src="f18-06-9780702032318"', //redbag: Redbag. Premature placental separation in a pony.
	'/src="_REPLACE_LOCATION__21897444.jpg"/' => 'src="f18-07-9780702032318"', //renal:  Renal cyst in a bovine kidney.
	'/src="_REPLACE_LOCATION__21897446.jpg"/' => 'src="f18-08-9780702032318"', //renal: Renal infarct in a cow.
	'/src="_REPLACE_LOCATION__21897448.jpg"/' => 'src="f18-09-9780702032318"', //respiratory: Severe respiratory distress in a cow with atypical pneumonia.  From Blowey RW, Weaver AD, Diseases and Disorders of Cattle, Mosby, 1997
	'/src="_REPLACE_LOCATION__21768908.jpg"/' => 'src="f18-10-9780702032318"', //retina: Retina of the dog.
	'/src="_REPLACE_LOCATION__21897450.jpg"/' => 'src="f18-11-9780702032318"', //retinal: Multifocal retinal dysplasia in an English springer spaniel.
	'/src="_REPLACE_LOCATION__21925648.jpg"/' => 'src="f18-12-9780702032318"', //Rhipicephalus: Rhipicephalus sanguineusmale (left) and female (right).
	'/src="_REPLACE_LOCATION__21925650.jpg"/' => 'src="f18-13-9780702032318"', //Rhipicephalus: Cow with a fairly large number of attached Rhipicephalus (Boophilus)annulatus ticks.
	'/src="_REPLACE_LOCATION__21897452.jpg"/' => 'src="f18-14-9780702032318"', //Rhodesian ridgeback: Rhodesian ridgeback showing the characteristic ridge.
	'/src="_REPLACE_LOCATION__21770001.jpg"/' => 'src="f18-15-9780702032318"', //rhythm: Electrocardiogram showing normal sinus rhythm in a dog.
	'/src="_REPLACE_LOCATION__21897454.jpg"/' => 'src="f18-16a-9780702032318"', //ringworm: Ringworm lesions in a horse.
	'/src="_REPLACE_LOCATION__21897455.jpg"/' => 'src="f18-16b-9780702032318"', //ringworm: Ringworm in calves caused by Trichophyton verrucosum.
	'/src="_REPLACE_LOCATION__21897456.jpg"/' => 'src="f18-16c-9780702032318"', //ringworm: Ringworm in a pig caused by Trichophyton spp.
	'/src="_REPLACE_LOCATION__21897457.jpg"/' => 'src="f18-16d-9780702032318"', //ringworm: Ringworm lesions in a dog caused by Microsporum canis.
	'/src="_REPLACE_LOCATION__21925668.jpg"/' => 'src="f18-17-9780702032318"', //Rochester-Carmalt forceps: Rochester Carmalt Forceps.
	'/src="_REPLACE_LOCATION__21897466.jpg"/' => 'src="f18-18-9780702032318"', //Romagna: Romagna beef bull.
	'/src="_REPLACE_LOCATION__21770903.jpg"/' => 'src="f18-19-9780702032318"', //Romanov: Romanov sheep.
	'/src="_REPLACE_LOCATION__21897468.jpg"/' => 'src="f18-20-9780702032318"', //Romney Marsh: Romney Marsh sheep.
	'/src="_REPLACE_LOCATION__21923470.jpg"/' => 'src="f18-21-9780702032318"', //rouleau: Marked rouleaux in a normal equine blood film. A neutrophilic band cell is also present. Hendrix CM, Sirois M, Laboratory Procedures for Veterinary Technicians, 5th Edition. Mosby, 2007.
	'/src="_REPLACE_LOCATION__21897470.jpg"/' => 'src="f18-22-9780702032318"', //rugae palatal: Palatal rugae in a dog.
	'/src="_REPLACE_LOCATION__21897472.jpg"/' => 'src="f18-23-9780702032318"', //rumenitis: Necrotizing, mycotic rumenitis in a steer following carbohydrate engorgement.
	'/src="_REPLACE_LOCATION__21897477.jpg"/' => 'src="f18-24-9780702032318"', //ruminal: Ruminal tympany in an 11-month-old calf due to compression of the esophagus by swelling in the ventral neck due to thymic lymphoma.
	'/src="_REPLACE_LOCATION__21923468.jpg"/' => 'src="f18-25-9780702032318"', //Russell bodies: Lymph node aspirate containing small lymphocytes, plasma cells, and one Mott cell with numerous Russell bodies (arrow). Cowell RL, et al, Diagnostic Cytology and Hematology of the Dog and Cat, 3rd Edition. Mosby,  2008.
	'/src="_REPLACE_LOCATION__21897481.jpg"/' => 'src="f19-01-9780702032318"', //sacroiliac: Schematic dorsal view of pelvis showing position of sacrum and short dorsal sacroiliac ligament (hatched lines) which runs from the tuber sacrale to the sacral spinous processes.
	'/src="_REPLACE_LOCATION__21923448.jpg"/' => 'src="f19-02-9780702032318"', //sacrum: Canine sacrum. A, Ventral view. B, Dorsal view.
	'/src="_REPLACE_LOCATION__21923428.jpg"/' => 'src="f19-03-9780702032318"', //sagittal: The sagittal plane. Christenson DE, Veterinary Medical Terminology. Saunders, 2008.
	'/src="_REPLACE_LOCATION__21772243.jpg"/' => 'src="f19-04-9780702032318"', //Saint Bernard, St. Bernard: Saint Bernard (shorthaired).
	'/src="_REPLACE_LOCATION__21772374.jpg"/' => 'src="f19-05-9780702032318"', //salivary gland: Salivary glands in the dog.
	'/src="_REPLACE_LOCATION__21897486.jpg"/' => 'src="f19-06-9780702032318"', //salmonellosis: Red discoloration of ears and snout with septicemic salmonellosis.
	'/src="_REPLACE_LOCATION__21923408.jpg"/' => 'src="f19-07-9780702032318"', //salpingitis: Avian salpingitis as a result of Salmonella infection.
	'/src="_REPLACE_LOCATION__21897787.jpg"/' => 'src="f19-08-9780702032318"', //Salter classification: Salter-Harris classification of physeal fractures.
	'/src="_REPLACE_LOCATION__21897788.jpg"/' => 'src="f19-09-9780702032318"', //Salter classification: Salter I fracture of the distal tibia in a cat.
	'/src="_REPLACE_LOCATION__21772672.jpg"/' => 'src="f19-10-9780702032318"', //Samoyed: Samoyed.
	'/src="_REPLACE_LOCATION__21772776.jpg"/' => 'src="f19-11-9780702032318"', //sandcrack: Sandcrack in horse's hoof.
	'/src="_REPLACE_LOCATION__21925689.jpg"/' => 'src="f19-12-9780702032318"', //Sarcocystis: Esophageal muscle with thick-walled sarcocyst of Sarcocystis hirsute.
	'/src="_REPLACE_LOCATION__21923391.jpg"/' => 'src="f19-13-9780702032318"', //sarcomere: Organization of proteins in a sarcomere.
	'/src="_REPLACE_LOCATION__21773186.jpg"/' => 'src="f19-14-9780702032318"', //Sarcoptes: Sarcoptes scabiei var. canis.
	'/src="_REPLACE_LOCATION__21897794.jpg"/' => 'src="f19-15-9780702032318"', //sarcoptic mange: Early sarcoptic mange in a piglet.
	'/src="_REPLACE_LOCATION__21897801.jpg"/' => 'src="f19-16-9780702032318"', //sarcoptic mange: Sarcoptic mange in a dog.
	'/src="_REPLACE_LOCATION__21897797.jpg"/' => 'src="f19-17-9780702032318"', //sarcoptic mange: Sarcoptic mange (footrot) in a ferret.
	'/src="_REPLACE_LOCATION__21897499.jpg"/' => 'src="f19-18-9780702032318"', //scald: Scald in a horse.
	'/src="_REPLACE_LOCATION__21925708.jpg"/' => 'src="f19-19-9780702032318"', //scaler: Jacquette Tartar Scalers.
	'/src="_REPLACE_LOCATION__21925711.jpg"/' => 'src="f19-20-9780702032318"', //scalpel: Scalpel Handles – #3, #4, and #8. Each handle size has a blade seat of a different size to accommodate a detachable blade.
	'/src="_REPLACE_LOCATION__21773584.jpg"/' => 'src="f19-21-9780702032318"', //scapula: Scapula of the dog.
	'/src="_REPLACE_LOCATION__21897503.jpg"/' => 'src="f19-22-9780702032318"', //Schirmer tear test (STT): Schirmer tear test being performed in a cat.
	'/src="_REPLACE_LOCATION__21925728.jpg"/' => 'src="f19-23-9780702032318"', //Schistosoma: Schistosoma manson.
	'/src="_REPLACE_LOCATION__21897505.jpg"/' => 'src="f19-24-9780702032318"', //schistosoma reflexus: Schistosoma reflexus in an aborted fetus.
	'/src="_REPLACE_LOCATION__21925748.jpg"/' => 'src="f19-25-9780702032318"', //schizocyte: Numerous schizocytes and a helmet-shaped cell in this blood smear from a dog with stenosis of the pulmonic valve.
	'/src="_REPLACE_LOCATION__21923388.jpg"/' => 'src="f19-26-9780702032318"', //schizont: Eimeria bovisschizont in an intestinal epithelial cell of a calf.
	'/src="_REPLACE_LOCATION__21897507.jpg"/' => 'src="f19-27-9780702032318"', //sciatic: Sciatic nerve paralysis from improper site of intramuscular injection.
	'/src="_REPLACE_LOCATION__21897509.jpg"/' => 'src="f19-28-9780702032318"', //scintiscan: Thyroid scintigraphy in a cat showing normal uptake in the thyroid lobes and sallivary glands.
	'/src="_REPLACE_LOCATION__21925768.jpg"/' => 'src="f19-29-9780702032318"', //scissors: Tenotomy Scissors.
	'/src="_REPLACE_LOCATION__21897511.jpg"/' => 'src="f19-30-9780702032318"', //sclera: Scleral hemorrhage in a calf.
	'/src="_REPLACE_LOCATION__21897513.jpg"/' => 'src="f19-31-9780702032318"', //sclerotium: Ergot sclerotia in some seed of a seed head of basin wildrye (Elymus cinereus).
	'/src="_REPLACE_LOCATION__21897515.jpg"/' => 'src="f19-32-9780702032318"', //scoliosis: Scoliosis in a newborn foal.
	'/src="_REPLACE_LOCATION__21774376.jpg"/' => 'src="f19-33-9780702032318"', //Scottish blackface: Scottish blackface sheep.
	'/src="_REPLACE_LOCATION__21774395.jpg"/' => 'src="f19-34-9780702032318"', //Scottish terrier: Scottish terrier.
	'/src="_REPLACE_LOCATION__21897517.jpg"/' => 'src="f19-35-9780702032318"', //scrapie: Scrapie.
	'/src="_REPLACE_LOCATION__21897521.jpg"/' => 'src="f19-36-9780702032318"', //screw: Carpal radiograph demonstrating transphyseal bridging using a "single screw" technique.
	'/src="_REPLACE_LOCATION__21923368.jpg"/' => 'src="f19-37-9780702032318"', //screw-worm: Larvae of a screw-worm fly, Cochliomyia hominivorax. Hendrix CM, Robinson, Diagnostic Parasitology for Veterinary Technicians, 4th Edition. Mosby, 2012.
	'/src="_REPLACE_LOCATION__21897523.jpg"/' => 'src="f19-38-9780702032318"', //sebaceous: Sebaceous adenoma.
	'/src="_REPLACE_LOCATION__21923928.jpg"/' => 'src="f19-39-9780702032318"', //seborrhea: Canine Primary Seborrhea. Generalized alopecia and lichenification affecting the entire cutaneous surface area.
	'/src="_REPLACE_LOCATION__21775092.jpg"/' => 'src="f19-40-9780702032318"', //seedy toe: Seedy toe in a horse's hoof.
	'/src="_REPLACE_LOCATION__21775128.jpg"/' => 'src="f19-41-9780702032318"', //segmentation: Segmentation movements of the small intestine.
	'/src="_REPLACE_LOCATION__21775281.jpg"/' => 'src="f19-42-9780702032318"', //selenium (Se): Coronary separation caused by selenium poisoning.
	'/src="_REPLACE_LOCATION__21925788.jpg"/' => 'src="f19-43-9780702032318"', //semicircular: Semicircular canals of the ear, showing locations of ampullae.
	'/src="_REPLACE_LOCATION__21925790.jpg"/' => 'src="f19-44-9780702032318"', //Senn retractor: Senn Rake Retractor.
	'/src="_REPLACE_LOCATION__21897525.jpg"/' => 'src="f19-45-9780702032318"', //sensitivity:  Agar plate antibiotic sensitivity test.
	'/src="_REPLACE_LOCATION__21923348.jpg"/' => 'src="f19-46-9780702032318"', //Setaria1: Setaria spp,the abdominal worm of cattle.
	'/src="_REPLACE_LOCATION__21776621.jpg"/' => 'src="f19-47-9780702032318"', //Shar Pei: Shar pei.
	'/src="_REPLACE_LOCATION__21776902.jpg"/' => 'src="f19-48-9780702032318"', //Shetland pony: Shetland Pony.
	'/src="_REPLACE_LOCATION__21897531.jpg"/' => 'src="f19-49-9780702032318"', //Shetland sheep: Shetland sheep.
	'/src="_REPLACE_LOCATION__21776923.jpg"/' => 'src="f19-50-9780702032318"', //shift: Canine blood smear showing a shift to the left with a segmented neutrophil (left) with toxic vacuolation and a metamyelocyte (right) with two Döhle bodies.
	'/src="_REPLACE_LOCATION__21776982.jpg"/' => 'src="f19-51-9780702032318"', //Shire: Shire horse.
	'/src="_REPLACE_LOCATION__21777125.jpg"/' => 'src="f19-52-9780702032318"', //Shorthorn: Shorthorn dual-purpose cow.
	'/src="_REPLACE_LOCATION__21777500.jpg"/' => 'src="f19-53-9780702032318"', //sidebone: Bones from horse with sidebone.
	'/src="_REPLACE_LOCATION__21777869.jpg"/' => 'src="f19-54-9780702032318"', //Simmental: Simmental dairy cow.
	'/src="_REPLACE_LOCATION__21897540.jpg"/' => 'src="f19-55-9780702032318"', //siresine: Siresine harness.
	'/src="_REPLACE_LOCATION__21778343.jpg"/' => 'src="f19-56-9780702032318"', //skin: Structure of mammalian skin.  From Mills PC, Cross SE: Transdermal drug delivery: Basic principles for the veterinarian Vet J 172:218-233, 2006. Elsevier.
	'/src="_REPLACE_LOCATION__21897519.jpg"/' => 'src="f19-57-9780702032318"', //skin: Demodexmites in a skin scraping (10x).
	'/src="_REPLACE_LOCATION__21778426.jpg"/' => 'src="f19-58-9780702032318"', //skin: Tenting of the skin due to dehydration.  From Bassert JM, McCurnin DM, McCurnin's Clinical Textbook for Veterinary Technicians, 7th ed, Saunders, 2010
	'/src="_REPLACE_LOCATION__21897542.jpg"/' => 'src="f19-59-9780702032318"', //skin: Intradermal allergy skin test.
	'/src="_REPLACE_LOCATION__21897856.jpg"/' => 'src="f19-60-9780702032318"', //skunk: Skunk.  Courtesy of CDC.
	'/src="_REPLACE_LOCATION__21778743.jpg"/' => 'src="f19-61-9780702032318"', //sling: Velpeau sling.
	'/src="_REPLACE_LOCATION__21897546.jpg"/' => 'src="f19-62-9780702032318"', //snout: Snout.
	'/src="_REPLACE_LOCATION__21780175.jpg"/' => 'src="f19-63-9780702032318"', //space: Types of dead-space in an anesthetic ventilation system.
	'/src="_REPLACE_LOCATION__21897548.jpg"/' => 'src="f19-64-9780702032318"', //spavin: Bog spavin.
	'/src="_REPLACE_LOCATION__21780619.jpg"/' => 'src="f19-65a-9780702032318"', //sperm: Primary spermatozoal abnormalities involving the head include double head (bicephaly) (A), small head (microcephaly) (B), large head (macrocephaly) (C), pear-shaped head (pyriform) (D), elongated head (E), and round head (F)..  From Hendrix CM, Sirois M, Laboratory Procedures for Veterinary Technicians, 5th Edition. Mosby, 2007.
	'/src="_REPLACE_LOCATION__21925808.jpg"/' => 'src="f19-65b-9780702032318"', //sperm: S-65bPrimary spermatozoal abnormalities involving the midpiece and tail include swollen midpiece (A), coiled midpiece and coiled tail (B), bent midpiece (C), double midpiece (D), and abaxial midpiece (E).
	'/src="_REPLACE_LOCATION__21780741.jpg"/' => 'src="f19-66-9780702032318"', //spermatozoon: Normal spermatozoon.
	'/src="_REPLACE_LOCATION__21897550.jpg"/' => 'src="f19-67a-9780702032318"', //Spirocerca: Caudal esophagus of a dog showing multiple parasitic nodules (white arrows) with red adult Spirocerca  lupiworms (black arrows). Bar = 5.5 cm.Spirocerca lupi infection in the dog: A review. Vet J 176:294-309, 2008. Elsevier.
	'/src="_REPLACE_LOCATION__21897551.jpg"/' => 'src="f19-67b-9780702032318"', //Spirocerca: Caudal esophagus of a dog showing a cauliflower-like mass which is a Spirocerca lupi-associated osteosarcoma with regional megaesophagus. Bar = 5 cm.Spirocerca lupi infection in the dog: A review. Vet J 176:294-309, 2008. Elsevier.
	'/src="_REPLACE_LOCATION__21897552.jpg"/' => 'src="f19-67c-9780702032318"', //Spirocerca: A thick-shelled larvated Spirocerca lupi egg (35 × 15μm).  From van der Merwe LL, Kirberger RM, Clift C, Williams M, Keller N, Naidoo V: Spirocerca lupi infection in the dog: A review. Vet J 176:294-309, 2008. Elsevier.
	'/src="_REPLACE_LOCATION__21897556.jpg"/' => 'src="f19-68-9780702032318"', //splayleg: Splayleg.
	'/src="_REPLACE_LOCATION__21897558.jpg"/' => 'src="f19-69-9780702032318"', //splint: Modified Thomas splint.
	'/src="_REPLACE_LOCATION__21897560.jpg"/' => 'src="f19-70-9780702032318"', //spondylosis: Spondylosis in a horse.
	'/src="_REPLACE_LOCATION__21781955.jpg"/' => 'src="f19-71-9780702032318"', //sporotrichosis: Sporotrichosis on a horse's shoulder.
	'/src="_REPLACE_LOCATION__21897564.jpg"/' => 'src="f19-72-9780702032318"', //squamous: Squamous cell carcinoma on the nonpigmented nasal planum of a cat.
	'/src="_REPLACE_LOCATION__21897566.jpg"/' => 'src="f19-73-9780702032318"', //squamous: Ocular plaque, an early precursor of ocular squamous cell carcinoma. Courtesy of B Vanselow .
	'/src="_REPLACE_LOCATION__21897570.jpg"/' => 'src="f19-74-9780702032318"', //stance: Arched back and painful stance due to mycoplasmal arthritis.
	'/src="_REPLACE_LOCATION__21897574.jpg"/' => 'src="f19-75-9780702032318"', //staples: Carpal radiograph demonstrating transphyseal bridging using a "staple".
	'/src="_REPLACE_LOCATION__21897578.jpg"/' => 'src="f19-76-9780702032318"', //stephanofilarosis: Stephanophilarosis (‘humpsore’) in a cow.
	'/src="_REPLACE_LOCATION__21783585.jpg"/' => 'src="f19-77-9780702032318"', //stifle: Stifle joint of the dog.
	'/src="_REPLACE_LOCATION__21784086.jpg"/' => 'src="f19-78-9780702032318"', //strabismus: Basic types of strabismus.
	'/src="_REPLACE_LOCATION__21897581.jpg"/' => 'src="f19-79-9780702032318"', //strabismus: Advanced bilateral convergent strabismus with exophthalmus in a German brown cow.
	'/src="_REPLACE_LOCATION__21897585.jpg"/' => 'src="f19-80-9780702032318"', //streaking: Plate inoculation and streaking method.
	'/src="_REPLACE_LOCATION__21897587.jpg"/' => 'src="f19-81-9780702032318"', //stringhalt: Classical sporadic stringhalt in a horse.
	'/src="_REPLACE_LOCATION__21784774.jpg"/' => 'src="f19-82-9780702032318"', //struvite: Struvite crystals in urine sediment.
	'/src="_REPLACE_LOCATION__21897590.jpg"/' => 'src="f19-83-9780702032318"', //strychnine: Convulsions in a strychnine-poisoned pig.
	'/src="_REPLACE_LOCATION__21897592.jpg"/' => 'src="f19-84-9780702032318"', //submandibular: Submandibular abscess in a cow.
	'/src="_REPLACE_LOCATION__21785790.jpg"/' => 'src="f19-85-9780702032318"', //Suffolk sheep: Suffolk meat sheep.
	'/src="_REPLACE_LOCATION__21786626.jpg"/' => 'src="f19-86-9780702032318"', //surgeon's knot: Surgeon's knot.
	'/src="_REPLACE_LOCATION__21897594.jpg"/' => 'src="f19-87-9780702032318"', //surgical: Basic components of a surgical instrument.
	'/src="_REPLACE_LOCATION__21786938.jpg"/' => 'src="f19-88-9780702032318"', //suture: Types of suture patterns: (A) simple continuous, (B) simple interrupted ( C ) Cruciate suture.
	'/src="_REPLACE_LOCATION__21897607.jpg"/' => 'src="f19-89-9780702032318"', //Swaledale sheep: Swaledale sheep.
	'/src="_REPLACE_LOCATION__21897065.jpg"/' => 'src="f19-90-9780702032318"', //swamp cancer: Swamp cancer (habronemiasis).
	'/src="_REPLACE_LOCATION__21897609.jpg"/' => 'src="f19-91-9780702032318"', //swelled head: Swelled head.
	'/src="_REPLACE_LOCATION__21897611.jpg"/' => 'src="f19-92-9780702032318"', //swine: Colitis in swine dysentery.
	'/src="_REPLACE_LOCATION__21897613.jpg"/' => 'src="f19-93-9780702032318"', //swinepox: Swinepox.
	'/src="_REPLACE_LOCATION__21923330.jpg"/' => 'src="f19-94-9780702032318"', //symphysis: Rostral view of feline mandibular symphysis.
	'/src="_REPLACE_LOCATION__21897615.jpg"/' => 'src="f19-95-9780702032318"', //syndactyly: Calf with syndactyly.
	'/src="_REPLACE_LOCATION__21787884.jpg"/' => 'src="f19-96-9780702032318"', //synechia: Lens subluxation in a fox terrier. A focal posterior synechia adheres to the anterior lens capsule at the 2 o'clock position.
	'/src="_REPLACE_LOCATION__21787976.jpg"/' => 'src="f19-97-9780702032318"', //synostosis: Congenital metatarsal bone synostosis in a dog.
	'/src="_REPLACE_LOCATION__21925828.jpg"/' => 'src="f19-98-9780702032318"', //synovial: Hinge-type synovial joint. Canine left elbow, lateral view.
	'/src="_REPLACE_LOCATION__21788463.jpg"/' => 'src="f20-01-9780702032318"', //tachycardia: Sinus tachycardia in a dog.
	'/src="_REPLACE_LOCATION__21925848.jpg"/' => 'src="f20-02-9780702032318"', //Taenia: Egg of Taenia pisiformis.
	'/src="_REPLACE_LOCATION__21788666.jpg"/' => 'src="f20-03-9780702032318"', //tail: Tail gland.
	'/src="_REPLACE_LOCATION__21897621.jpg"/' => 'src="f20-04-9780702032318"', //tailing: Cow in the process of being restrained by tailing.
	'/src="_REPLACE_LOCATION__21897623.jpg"/' => 'src="f20-05-9780702032318"', //tapetum: Tapetum of the dog.
	'/src="_REPLACE_LOCATION__21923328.jpg"/' => 'src="f20-06-9780702032318"', //tapeworm: Example of typical tapeworm with dorsoventrally flattened, ribbonlike appearance. Hendrix CM, Robinson E, Diagnostic Parasitology for Veterinary Technicians, 4th Edition. Mosby, 2012.
	'/src="_REPLACE_LOCATION__21897858.jpg"/' => 'src="f20-07-9780702032318"', //tarantula: Tarantula spider. Courtesy of CDC.
	'/src="_REPLACE_LOCATION__21925888.jpg"/' => 'src="f20-08-9780702032318"', //tartar: Accumulation of dental tartar and resulting perialveolar gingivitis in cat.
	'/src="_REPLACE_LOCATION__21789383.jpg"/' => 'src="f20-09-9780702032318"', //Taxus: Taxus baccata.
	'/src="_REPLACE_LOCATION__21897625.jpg"/' => 'src="f20-10-9780702032318"', //teat: Teat necrosis.
	'/src="_REPLACE_LOCATION__21925928.jpg"/' => 'src="f20-11-9780702032318"', //teat: Teat Slitter.
	'/src="_REPLACE_LOCATION__21897629.jpg"/' => 'src="f20-10-9780702032318"', //teeth: Needle teeth of piglet, commonly clipped at birth so they do not cause injury to other piglets or sow’s mammary gland.
	'/src="_REPLACE_LOCATION__21897633.jpg"/' => 'src="f20-13-9780702032318"', //teeth: Pink teeth in Holstein calf with inherited osteogenesis and dysplastic dentine.
	'/src="_REPLACE_LOCATION__21897631.jpg"/' => 'src="f20-14-9780702032318"', //teeth: Retained deciduous canine tooth in a dog.
	'/src="_REPLACE_LOCATION__21790258.jpg"/' => 'src="f20-15-9780702032318"', //tendon: Congenital contracture of flexor tendons.
	'/src="_REPLACE_LOCATION__21897635.jpg"/' => 'src="f20-16-9780702032318"', //tenesmus: Tenesmus.
	'/src="_REPLACE_LOCATION__21897637.jpg"/' => 'src="f20-17-9780702032318"', //teratoma: Teratoma from a dog.  From van Dijk JE, Gruys E, Mouwen JMVM, Color Atlas of Veterinary Pathology, 2nd ed, Saunders, 2007
	'/src="_REPLACE_LOCATION__21791248.jpg"/' => 'src="f20-18-9780702032318"', //Texas longhorn: Texas longhorn cattle.
	'/src="_REPLACE_LOCATION__21791262.jpg"/' => 'src="f20-19-9780702032318"', //Texel: Texel meat sheep.
	'/src="_REPLACE_LOCATION__21923308.jpg"/' => 'src="f20-20-9780702032318"', //Thelazia: Thelaziain a dog's conjunctival sac.
	'/src="_REPLACE_LOCATION__21897641.jpg"/' => 'src="f20-21-9780702032318"', //thelitis:  Theilitis in a ewe with contagious ecthyma.
	'/src="_REPLACE_LOCATION__21792078.jpg"/' => 'src="f20-22-9780702032318"', //third eyelid: Eversion of the third eyelid in a dog.
	'/src="_REPLACE_LOCATION__21792278.jpg"/' => 'src="f20-23-9780702032318"', //Thoroughbred: English thoroughbred horse.
	'/src="_REPLACE_LOCATION__21792290.jpg"/' => 'src="f20-24-9780702032318"', //thoroughpin: Thoroughpin.
	'/src="_REPLACE_LOCATION__21897643.jpg"/' => 'src="f20-25-9780702032318"', //thrombocytopenia: Punctate petechial hemorrhages of the colonic mucosa, a consequence of thrombocytopenia.
	'/src="_REPLACE_LOCATION__21923288.jpg"/' => 'src="f20-26-9780702032318"', //thrombophlebitis: Jugular thrombophlebitis The jugular vein has a large thrombus (arrow) attached to the wall at the site of prolonged catheterization.
	'/src="_REPLACE_LOCATION__21792926.jpg"/' => 'src="f20-27-9780702032318"', //thyroid: Microscopic appearance of the thyroid gland.
	'/src="_REPLACE_LOCATION__21793672.jpg"/' => 'src="f20-28-9780702032318"', //Toggenburg: Toggenburg goat.
	'/src="_REPLACE_LOCATION__21925968.jpg"/' => 'src="f20-29-9780702032318"', //tomography: A, Dog in position for a brain computed tomogram. B, Computed tomography (CT) control panel located outside the shielded CT room.
	'/src="_REPLACE_LOCATION__21793954.jpg"/' => 'src="f20-30-9780702032318"', //tonometer: Principles of the tonometer.
	'/src="_REPLACE_LOCATION__21897645.jpg"/' => 'src="f20-31-9780702032318"', //tonometer: Schiøtz tonometer.
	'/src="_REPLACE_LOCATION__21897647.jpg"/' => 'src="f20-32-9780702032318"', //tooth: Basic anatomy of the tooth and periodontium.
	'/src="_REPLACE_LOCATION__21794268.jpg"/' => 'src="f20-33-9780702032318"', //Toulouse: Toulouse geese.
	'/src="_REPLACE_LOCATION__21897753.jpg"/' => 'src="f20-34-9780702032318"', //toxoplasmosis: Toxoplasma gondiitachyzoites (arrows) appear as small crescent-shaped bodies with a light blue cytoplasm and a dark-staining pericentral nucleus.
	'/src="_REPLACE_LOCATION__21897649.jpg"/' => 'src="f20-35-9780702032318"', //tracheostomy: Tracheostomy in a horse.
	'/src="_REPLACE_LOCATION__21794876.jpg"/' => 'src="f20-36-9780702032318"', //Trakehner horse: Trakehner horse.
	'/src="_REPLACE_LOCATION__21897651.jpg"/' => 'src="f20-37-9780702032318"', //Triadan system: Triadan tooth numbering system in the dog. (A) Maxilla. (B) Mandible.
	'/src="_REPLACE_LOCATION__21897860.jpg"/' => 'src="f20-38-9780702032318"', //Triatoma: Triatoma infestans (kissing bug). Courtesy of CDC/WHO.
	'/src="_REPLACE_LOCATION__21897653.jpg"/' => 'src="f20-39-9780702032318"', //trichiasis: Medial entropion and trichiasis in a pug, with resulting nasal keratitis and pigmentation.
	'/src="_REPLACE_LOCATION__21796072.jpg"/' => 'src="f20-40-9780702032318"', //Trichodectes: Trichodectes canis.
	'/src="_REPLACE_LOCATION__21926008.jpg"/' => 'src="f20-41-9780702032318"', //trichogram: Trichogram. Microscopic image of a telogen hair root (left) and an anagen hair root (right).
	'/src="_REPLACE_LOCATION__21897655.jpg"/' => 'src="f20-42-9780702032318"', //Trichophyton: Trichophyton mentagrophytes infection of four years duration in a dog.
	'/src="_REPLACE_LOCATION__21897659.jpg"/' => 'src="f20-43a-9780702032318"', //Trichuris: Adult Trichuris vulpis,the canine whipworm.
	'/src="_REPLACE_LOCATION__21897862.jpg"/' => 'src="f20-43b-9780702032318"', //Trichuris: Egg of Trichuris vulpis. Courtesy of CDC/Dr Mae Melvin.
	'/src="_REPLACE_LOCATION__21796790.jpg"/' => 'src="f20-44-9780702032318"', //Tritrichomonas: Electron micrograph Tritrichomonas foetus.
	'/src="_REPLACE_LOCATION__21797115.jpg"/' => 'src="f20-45-9780702032318"', //Trotter: Trotter horse.
	'/src="_REPLACE_LOCATION__21735046.jpg"/' => 'src="f20-46-9780702032318"', //tube: Nasogastric intubation of bovine with a foal stomach tube.
	'/src="_REPLACE_LOCATION__21897668.jpg"/' => 'src="f20-47-9780702032318"', //tuberculin: Tuberculin single intradermal test (SIT). Positive tuberculosis test. Swelling at 72 hours following intradermal injection of bovine tuberculin.
	'/src="_REPLACE_LOCATION__21897670.jpg"/' => 'src="f20-48-9780702032318"', //tuberculin:  Single intradermal comparative cervical tuberculin (SICCT) test. Positive tuberculosis test. Swelling at 72 hours following intradermal injection of tuberculin. Reaction is greater at site of bovine tuberculin injection (red) than avian tuberculin (white).
	'/src="_REPLACE_LOCATION__21897672.jpg"/' => 'src="f20-49-9780702032318"', //turbinal, turbinate: Abscesses in the mucous membranes of the turbinate (nasal concha).  Postmortem dissection of a sheep with actinobacillosis.
	'/src="_REPLACE_LOCATION__21798307.jpg"/' => 'src="f20-50-9780702032318"', //twitch: Chain Twitch.
	'/src="_REPLACE_LOCATION__21897676.jpg"/' => 'src="f20-51-9780702032318"', //twitch: Humane twitch.
	'/src="_REPLACE_LOCATION__21798399.jpg"/' => 'src="f20-52-9780702032318"', //tympanic: Normal canine left tympanic membrane.
	'/src="_REPLACE_LOCATION__21798611.jpg"/' => 'src="f20-53-9780702032318"', //Tyrol grey cattle: Tyrol dual-purpose cow.
	'/src="_REPLACE_LOCATION__21897678.jpg"/' => 'src="f21-01-9780702032318"', //ulcer: Dendritic ulcers stained with fluorescein in a cat with herpesvirus infection.
	'/src="_REPLACE_LOCATION__21896786.jpg"/' => 'src="f21-02a-9780702032318"', //ulcerative: Ulcerative dermatosis of the penis in a ram.
	'/src="_REPLACE_LOCATION__21896787.jpg"/' => 'src="f21-02b-9780702032318"', //ulcerative: Ulcerative dermatosis on the vulva of a ewe.
	'/src="_REPLACE_LOCATION__21924268.jpg"/' => 'src="f21-03-9780702032318"', //ultrasound: Parasagittal ultrasound image of the caudal abdomen of a dog with pyometra. Note the enlarged, fluid-filled uterus (arrows).
	'/src="_REPLACE_LOCATION__21799134.jpg"/' => 'src="f21-04-9780702032318"', //umbilical: Umbilical hernia in a foal.
	'/src="_REPLACE_LOCATION__21897680.jpg"/' => 'src="f21-05-9780702032318"', //umbilical: Fresh abortion with twisting, edema and hemorrhage of the umbilical cord in a foal.
	'/src="_REPLACE_LOCATION__21897682.jpg"/' => 'src="f21-06-9780702032318"', //undershot: Undershot jaw.
	'/src="_REPLACE_LOCATION__21897684.jpg"/' => 'src="f21-07-9780702032318"', //urachus: Patent urachus in colt foal. Urine can be seen flowing from both the penis and the urachus simultaneously.
	'/src="_REPLACE_LOCATION__21799988.jpg"/' => 'src="f21-08-9780702032318"', //urethral: Subcutaneous swelling containing urine due to urolithiasis and urethral perforation.
	'/src="_REPLACE_LOCATION__21896763.jpg"/' => 'src="f21-09-9780702032318"', //urolith: Cystine uroliths (stones).
	'/src="_REPLACE_LOCATION__21897687.jpg"/' => 'src="f21-10-9780702032318"', //uroperitoneum: Abdominal distension associated with a ruptured bladder and uroperitoneum.
	'/src="_REPLACE_LOCATION__21897689.jpg"/' => 'src="f21-11-9780702032318"', //urticaria:  Urticarial lesions ("hives") in a dog.
	'/src="_REPLACE_LOCATION__21897691.jpg"/' => 'src="f21-12-9780702032318"', //uveitis: Foal with uveitis with green hue of the iris and fibrin in the anterior chamber.  From McAuliffe SB, Slovis NM, Color Atlas of Diseases and Disorders of the Foal, Saunders, 2008
	'/src="_REPLACE_LOCATION__21801554.jpg"/' => 'src="f22-01-9780702032318"', //valgus: Bilateral carpal valgus deformity in a foal.
	'/src="_REPLACE_LOCATION__21897693.jpg"/' => 'src="f22-02-9780702032318"', //varus: Carpal varus deformity.
	'/src="_REPLACE_LOCATION__21924248.jpg"/' => 'src="f22-03-9780702032318"', //vasculitis: Cutaneous Vasculitis. Peripheral edema caused by vascular leakage associated with vasculitis.
	'/src="_REPLACE_LOCATION__21896673.jpg"/' => 'src="f22-04-9780702032318"', //vena: Ultrasonogram of liver and normal caudal vena cava imaged from the 11th intercostal space using a 3.5 MHz linear transducer. The caudal vena cava has a triangular shape on cross section. 1 – abdominal wall, 2 – liver, 3 – caudal vena cava; Ds, dorsal, Vt, ventral.
	'/src="_REPLACE_LOCATION__21896677.jpg"/' => 'src="f22-05-9780702032318"', //vena: Epistaxis and bleeding from the mouth in a cow with pulmonary hemorrhage resulting from caudal vena caval thrombosis.
	'/src="_REPLACE_LOCATION__21802508.jpg"/' => 'src="f22-06-9780702032318"', //venereal: Canine transmissible venereal tumor on the penis.
	'/src="_REPLACE_LOCATION__21802533.jpg"/' => 'src="f22-07-9780702032318"', //venipuncture: Dog positioned for venipuncture of the jugular vein.
	'/src="_REPLACE_LOCATION__21802750.jpg"/' => 'src="f22-08-9780702032318"', //ventricle: Ventricular system of the brain.
	'/src="_REPLACE_LOCATION__21897700.jpg"/' => 'src="f22-09-9780702032318"', //ventricular: Schematic representation of a ventricular septal defect.
	'/src="_REPLACE_LOCATION__21897702.jpg"/' => 'src="f22-10-9780702032318"', //verminous: Verminous arteritis of teh anterior mesenteric artery of a horse.
	'/src="_REPLACE_LOCATION__21803442.jpg"/' => 'src="f22-11-9780702032318"', //vesicular: Ruptured vesicles on the oral mucosa of a calf with vesicular stomatitis infection.
	'/src="_REPLACE_LOCATION__21803990.jpg"/' => 'src="f22-12-9780702032318"', //villus: Organization of an intestinal villus.
	'/src="_REPLACE_LOCATION__21897704.jpg"/' => 'src="f22-13-9780702032318"', //visna: Visna, showing weakness of the hindlimbs and abnormal stance. The animal is leaning against the wall and is unable to remain upright.
	'/src="_REPLACE_LOCATION__21897706.jpg"/' => 'src="f22-14-9780702032318"', //vitiligo: Vitiligo that developed in an adult Rottweiler.
	'/src="_REPLACE_LOCATION__21804829.jpg"/' => 'src="f22-15-9780702032318"', //Vogt–Koyanagi–Harada-like syndrome: Vogt–Koyanagi–Harada-like syndrome.
	'/src="_REPLACE_LOCATION__21897708.jpg"/' => 'src="f22-16-9780702032318"', //volvulus: Volvulus of the small intestine in a pig.
	'/src="_REPLACE_LOCATION__21897713.jpg"/' => 'src="f23-01-9780702032318"', //wasting: Wasting. Ewe showing severe weight loss.
	'/src="_REPLACE_LOCATION__21805697.jpg"/' => 'src="f23-02-9780702032318"', //wattle1: Avian wattles and comb.
	'/src="_REPLACE_LOCATION__21897715.jpg"/' => 'src="f23-03-9780702032318"', //wedge: Wedge gag.
	'/src="_REPLACE_LOCATION__21806038.jpg"/' => 'src="f23-04-9780702032318"', //Welsh mountain sheep: Welsh mountain wool sheep.
	'/src="_REPLACE_LOCATION__21897717.jpg"/' => 'src="f23-05-9780702032318"', //Welsh pony: Welsh Pony.
	'/src="_REPLACE_LOCATION__21806055.jpg"/' => 'src="f23-06-9780702032318"', //Welsh springer spaniel: Welsh springer spaniel.
	'/src="_REPLACE_LOCATION__21806101.jpg"/' => 'src="f23-07-9780702032318"', //West African dwarf goat: West African dwarf goat.
	'/src="_REPLACE_LOCATION__21897864.jpg"/' => 'src="f23-08-9780702032318"', //West Nile virus (WNV): Transmission electron micrograph of the West Nile virus (WNV). Courtesy of CDC/PE Rollin.
	'/src="_REPLACE_LOCATION__21806301.jpg"/' => 'src="f23-09-9780702032318"', //wheelbarrowing: Wheelbarrowing test.
	'/src="_REPLACE_LOCATION__21898008.jpg"/' => 'src="f23-10-9780702032318"', //white-nose syndrome: White-nose syndrome in Hailes Cave, Albany County, NY. Photo provided by the New York State Department of Environmental Conservation. All rights reserved.
	'/src="_REPLACE_LOCATION__21897726.jpg"/' => 'src="f23-11-9780702032318"', //windswept: Windswept foal.
	'/src="_REPLACE_LOCATION__21897728.jpg"/' => 'src="f23-12-9780702032318"', //Wood's light, lamp: Apple green fluorescence under a Wood's lamp of hairs infected with Microsporum canis.
	'/src="_REPLACE_LOCATION__21897791.jpg"/' => 'src="f23-13-9780702032318"', //wool:  Wool fiber, non-medulated and with imbricated surface compared with medullated hair fiber.
	'/src="_REPLACE_LOCATION__21896613.jpg"/' => 'src="f23-14-9780702032318"', //wool: Wool pulling in sheep (with Damalinia ovis)  occurring at the areas of the fleece that can be reached with the mouth.
	'/src="_REPLACE_LOCATION__21897732.jpg"/' => 'src="f23-15-9780702032318"', //wool: Break line in staple of tender wool.
	'/src="_REPLACE_LOCATION__21897734.jpg"/' => 'src="f23-16-9780702032318"', //woolly haircoat syndrome: Woolly haircoat syndrome. Calf dead from cardiomyopathy.
	'/src="_REPLACE_LOCATION__21924228.jpg"/' => 'src="f25-01-9780702032318"', //Yersinia: Yersinia pestis on sheep blood agar after 72 hours' incubation.
	'/src="_REPLACE_LOCATION__21924168.jpg"/' => 'src="f26-01-9780702032318"', //Ziehl-Neelsen stain: Ziehl-Neelsen stain. Acid-fast organisms mycobacterial organisms (arrowheads) are strongly stained bright red. Note the presence of unstained bacilli (arrows in white).
	'/src="_REPLACE_LOCATION__21897736.jpg"/' => 'src="f26-02-9780702032318"', //zinc (Zn): Abomasal necrosis in a ewe that drank from a zinc sulfate footbath solution.
);
?>