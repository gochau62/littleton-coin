/*-------------------------------------------------------------------*/
/*                                                                   */
/* Program Name      - SBLCATDFT.SQL                                 */
/*                                                                   */
/* Program Narrative - Seed the Sellbrite Bulk Loader category       */
/*                     default table by merging the category_copy    */
/*                     and category_meta VLOOKUP blocks.             */
/*                     Run after SBLCATDFT.TABLE.                    */
/*                                                                   */
/* Author            - Gordon Chau                                   */
/*                     Littleton NH                                  */
/*                                                                   */
/* Date Written      - 06/29/2026                                    */
/* Run Instructions  - RUNSQLSTM SRCFILE(LSCDEVLIBP/QSQLSRC)         */
/*                     SRCMBR(SBLCATDFT) COMMIT(*NONE)               */
/*                                                                   */
/*********************************************************************/
/*                                                                   */
/* __________________________________________________________________*/
/* |                  Maintenance History                           |*/
/* |  Author   -                                                    |*/
/* |   Date    -                                                    |*/
/* |   Purpose -                                                    |*/
/* ------------------------------------------------------------------*/
/*-------------------------------------------------------------------*/

DELETE FROM LSCDEVLIBP.SBLCATDFT;

INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Liberty Cap Half Cent', 'Liberty Cap', '1/2C', 'Copper', NULL, 'United States', 'U.S. Mint', 'liberty cap half cent penny type united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Half Cent', 'Draped Bust', '1/2C', 'Copper', NULL, 'United States', 'U.S. Mint', 'draped bust half cent penny type united states mint coin coinage numismatics money', 0.011875, 'Struck over 200 years ago, this scarce copper half cent bears Robert Scot''s classic Draped Bust design. The obverse shows Liberty with a ribbon in her flowing hair and a plunging neckline draped with linen. The reverse bears UNITED STATES OF AMERICA and the fraction 1/200 beneath a laurel wreath and HALF CENT.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Classic Head Half Cent', 'Classic Head', '1/2C', 'Copper', NULL, 'United States', 'U.S. Mint', 'classic head half cent penny type united states mint coin coinage numismatics money', 0.01125, 'Struck over 180 years ago, this scarce copper half cent bears John Reich''s Classic Head design. The obverse depicts Liberty with curly hair held by a band inscribed LIBERTY. The reverse bears HALF CENT surrounded by a laurel wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Braided Hair Half Cent', 'Braided Hair', '1/2C', 'Copper', NULL, 'United States', 'U.S. Mint', 'braided hair half cent penny type united states mint coin coinage numismatics money', 0.011875, 'This scarce copper Braided Hair half cent was struck over 150 years ago. It bears Christian Gobrecht''s classic design. The obverse depicts Liberty wearing a diadem, her long hair in a bun held by beaded cords. The reverse shows HALF CENTS surrounded by a wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Flowing Hair Large Cent', 'Flowing Hair', '1C', 'Copper', NULL, 'United States', 'U.S. Mint', 'flowing hair large cent penny type united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Liberty Cap Large Cent', 'Liberty Cap', '1C', 'Copper', NULL, 'United States', 'U.S. Mint', 'liberty cap large cent penny type united states mint coin coinage numismatics money', 0.029, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Large Cent', 'Draped Bust', '1C', 'Copper', NULL, 'United States', 'U.S. Mint', 'draped bust large cent penny type united states mint coin coinage numismatics money', 0.0225, 'Struck over 200 years ago, this copper Draped Bust large cent is 50% larger than today''s cents. Its classic obverse by Robert Scot depicts Liberty with a plunging neckline and flowing hair held by a ribbon. The reverse shows ONE CENT, surrounded by a wreath, UNITED STATES OF AMERICA and the fraction 1/100.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Classic Head Large Cent', 'Classic Head', '1C', 'Copper', NULL, 'United States', 'U.S. Mint', 'classic head large cent penny type united states mint coin coinage numismatics money', 0.0225, 'Struck over 200 years ago, this copper Classic Head large cent is 50% larger than today''s cents. Its classic obverse by John Reich depicts 13 stars around Liberty, her curly hair held by a band inscribed LIBERTY. The reverse bears ONE CENT inside a laurel wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Coronet Head Large Cent', 'Coronet Head', '1C', 'Copper', NULL, 'United States', 'U.S. Mint', 'coronet head large cent penny type united states mint coin coinage numismatics money', 0.023125, 'Struck over 180 years ago, this copper Coronet Head large cent - also called the Matron Head large cent - is 50% larger than today''s cents. It depicts a more serious Liberty, with a diadem inscribed LIBERTY and long hair in a bun held by plain cords. The reverse shows ONE CENT surrounded by a laurel wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Braided Hair Large Cent', 'Braided Hair', '1C', 'Copper', NULL, 'United States', 'U.S. Mint', 'braided hair large cent penny type united states mint coin coinage numismatics money', 0.023125, 'Struck over 150 years ago, this copper Braided Hair large cent bears Christian Gobrecht''s classic design. Its obverse depicts Liberty in a diadem, her long tresses held in a bun held by two beaded cords. The reverse bears ONE CENT encircled by a wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Flying Eagle Small Cent', 'Flying Eagle', '1C', 'Copper-Nickel', NULL, 'United States', 'U.S. Mint', 'flying eagle small cent penny type united states mint coin coinage numismatics money', 0.01, 'Briefly minted from 1856-1858, Flying Eagle cents were America''s first small-size 1c coins. Struck in 88% copper and 12% nickel, these coins feature an eagle in flight on the obverse and a wreath of corn, wheat, cotton and tobacco on the reverse.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Indian Head Small Cent', 'Indian Head', '1C', NULL, NULL, 'United States', 'U.S. Mint', 'indian head small cent penny type united states mint coin coinage numismatics money', 0.006875, 'Struck from 1859-1864, copper-nickel Indian Head cents depict Liberty with an Indian headdress and not an actual Native American. The reverse of first-year coins featured a laurel wreath, which changed in 1860 to an oak wreath and small shield.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Lincoln Wheat Small Cent', 'Lincoln Wheat', '1C', NULL, NULL, 'United States', 'U.S. Mint', 'lincoln wheat small cent penny united states mint coin coinage numismatics money', 0.006875, 'Lincoln cents with the original Wheat Ears reverse were introduced in 1909 on the 100th anniversary of Abraham Lincoln''s birth and were struck until 1958. These bronze cents were the first circulating U.S. coins to depict an actual person.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Lincoln Memorial Small Cent', 'Lincoln Memorial', '1C', NULL, NULL, 'United States', 'U.S. Mint', 'lincoln memorial small cent penny united states mint coin coinage numismatics money', 0.006875, 'The Lincoln cent was redesigned in 1959 to celebrate the 150th anniversary of Abraham Lincoln''s birth. The obverse continued to depict Lincoln''s portrait, while Frank Gasparro''s new reverse depicted the Lincoln Memorial.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Lincoln Bicentennial Small Cent', 'Lincoln Bicentennial', '1C', NULL, NULL, 'United States', 'U.S. Mint', 'lincoln bicentennial small cent penny united states mint coin coinage numismatics money', 0.006875, 'In honor of the bicentennial of Abraham Lincoln''s birth and the 100th anniversary of the Lincoln cent, this special one-year-only cent bears a special reverse design depicting the first of four major stages in Abraham Lincoln''s life.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Lincoln Shield Small Cent', 'Lincoln Shield', '1C', 'Copper-Plated Zinc', NULL, 'United States', 'U.S. Mint', 'lincoln shield small cent penny united states mint coin coinage numismatics money', 0.006875, 'Lincoln cents issued 2010-date bear a new reverse by Lyndall Bass, which depicts a "Union Shield" to represent President Lincoln''s preservation of the United States as a single, unified nation. The obverse bears Victor D. Brenner''s original portrait design.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Two Cent', NULL, '2C', 'Bronze', NULL, 'United States', 'U.S. Mint', 'two cent shield type united states mint coin coinage numismatics money', 0.01375, 'This century-old bronze two-cent piece is from the first U.S. coin series to bear IN GOD WE TRUST. The motto appears on the obverse, on a ribbon above the Union Shield. Behind the shield are two crossed arrows and an encircling wreath. The reverse bears 2 CENTS surrounded by two sheaves of wheat tied by a ribbon and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Three Cent Silver', 'Silver', '3C', 'Silver', NULL, 'United States', 'U.S. Mint', 'three cent silver trime type united states mint coin coinage numismatics money', 0.001875, 'This century-old silver three-cent piece (or "Trime") is the smallest-denomination silver coin ever issued by the U.S. Mint. Its obverse depicts a Union Shield atop a six-pointed star. UNITED STATES OF AMERICA and the date encircle the design. The reverse features the Roman numeral III surrounded by a large, stylized letter C and 13 stars.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Three Cent Nickel', 'Nickel', '3C', 'Copper-Nickel', NULL, 'United States', 'U.S. Mint', 'three cent nickel type united states mint coin coinage numismatics money', 0.004375, 'Struck over 130 years ago, this nickel three-cent piece bears James B. Longacre''s classic design. It depicts Liberty in a diadem inscribed LIBERTY, her long hair in an intricate braid. The reverse bears the Roman numeral III inside by a laurel wreath.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Flowing Hair Half Dime', 'Flowing Hair', '5C', 'Silver', '0.8924', 'United States', 'U.S. Mint', 'flowing hair half dime silver type united states mint coin coinage numismatics money', NULL, 'Struck over 200 years ago, this silver Flowing Hair half dime is one of America''s oldest federal-issue coins. Its obverse bears Robert Scot''s classic image of Liberty with long hair flowing to her shoulders. Surrounding her are 13 stars, with the date below. The reverse depicts an American eagle encircled by a laurel wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Half Dime', 'Draped Bust', '5C', 'Silver', '0.8924', 'United States', 'U.S. Mint', 'draped bust half dime silver type united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Capped Bust Half Dime', 'Capped Bust', '5C', 'Silver', NULL, 'United States', 'U.S. Mint', 'capped bust half dime silver type united states mint coin coinage numismatics money', 0.0025, 'Struck over 180 years ago, this silver Capped Bust half dime bears John Reich''s classic design. It depicts 13 stars around Liberty in a cloth cap, her curly hair reaching her shoulder where a brooch secures her gown. The reverse bears UNITED STATES OF AMERICA around an American eagle with Union Shield holding an olive branch and three arrows. The denomination 5 c. is below the eagle, with E PLURIBUS UNUM on a banner above.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Seated Half Dime', 'Seated Liberty', '5C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'seated liberty half dime silver type united states mint coin coinage numismatics money', 0.0025, 'This century-old, 90% silver Liberty Seated half dime is from the final series before the denomination was retired. Its obverse depicts Liberty seated on a rock holding a Union Shield and a Liberty cap atop a pole. The reverse bears HALF DIME surrounded by a laurel wreath and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Shield Nickel', 'Shield', '5C', 'Copper-Nickel', NULL, 'United States', 'U.S. Mint', 'shield nickel type united states mint coin coinage numismatics money', 0.010625, 'The classic Shield nickel of 1866-1883 replaced the silver half dimes of 1794-1873. The coin''s obverse depicts a shield and wreath, while the reverse features a large numeral "5" inside a circle of 13 stars above the word CENTS.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Liberty Nickel', 'Liberty', '5C', 'Copper-Nickel', NULL, 'United States', 'U.S. Mint', 'liberty nickel type united states mint coin coinage numismatics money', 0.01125, 'When first issued in 1883, Liberty Head Nickels (or "V" nickels) bore just a large Roman numeral "V" for the denomination. So the Mint soon added the word CENTS to stop con artists from gold plating them and passing them off as $5 gold pieces.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Buffalo Nickel', 'Buffalo', '5C', 'Copper-Nickel', NULL, 'United States', 'U.S. Mint', 'buffalo nickel indian head type united states mint coin coinage numismatics money', 0.01125, 'Also called the Indian Head nickel, the prized Buffalo nickel is considered the most uniquely American coin. Designed by sculptor James E. Fraser, the obverse depicts a proud Native American. The distinctive reverse features an American bison.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Jefferson Nickel', 'Jefferson', '5C', NULL, '0.35', 'United States', 'U.S. Mint', 'jefferson nickel united states mint coin coinage numismatics money', 0.01125, 'The Jefferson nickel offers collectors an affordable way to build a complete collection. The obverse bears a striking portrait of America''s founding father and 3rd president, while the reverse depicts his home, Monticello.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Dime', 'Draped Bust', '10C', 'Silver', '0.8924', 'United States', 'U.S. Mint', 'draped bust dime silver type united states mint coin coinage numismatics money', 0.005, 'Struck over 200 years ago, this classic Draped Bust silver dime designed by Robert Scot depicts Liberty with a plunging neckline and a ribbon in her flowing hair. 13 stars surround her, with LIBERTY above and the date below. The reverse bears a large heraldic eagle patterned after the Great Seal of the United States.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Capped Bust Dime', 'Capped Bust', '10C', 'Silver', NULL, 'United States', 'U.S. Mint', 'capped bust dime silver type united states mint coin coinage numismatics money', 0.005, 'Designed by John Reich, this classic silver dime depicts Liberty on the obverse with hair beneath a cloth cap surrounded by 13 stars. Its reverse features the denomination 10 c. beneath an eagle with shield on a branch holding three arrows.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Seated Liberty Dime', 'Seated Liberty', '10C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'seated liberty dime silver type united states mint coin coinage numismatics money', 0.005, 'Designed by Christian Gobrecht, this 90% silver dime depicts Liberty seated on a rock, holding a shield in her right hand and a Liberty cap on a pole in her left. The reverse displays a wreath, the denomination and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Barber Dime', 'Barber', '10C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'barber dime liberty head silver type united states mint coin coinage numismatics money', 0.005, 'Known as the Barber dime after its designer, this classic Liberty Head dime was struck in 90% silver. Its obverse depicts Liberty facing right wearing a Phrygian cap and laurel wreath, while the reverse shows a wreath and the denomination ONE DIME.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mercury Dime', 'Mercury', '10C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'mercury dime winged liberty head silver united states mint coin coinage numismatics money', 0.005625, 'This 90% silver Mercury dime was designed by A.A. Weinman, who also created the Liberty Walking half dollars of 1916-1947. Its obverse features Liberty in a winged cap to symbolize freedom of thought. The reverse depicts a fasces against a branch in the background.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Roosevelt Dime', 'Roosevelt', '10C', NULL, NULL, 'United States', 'U.S. Mint', 'roosevelt dime united states mint coin coinage numismatics money', 0.005625, 'This dime honors America''s 32nd and only four-term president, Franklin D. Roosevelt, Designed by John R. Sinnock, the obverse depicts Roosevelt''s portrait. The reverse shows a torch between olive and oak branches, symbolizing peace, strength and independence.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Semiquincentennial Dime', 'Semiquincentennial', '10C', NULL, '0.999', 'United States', 'U.S. Mint', 'semiquincentennial dime united states mint coin coinage numismatics money', 0.005625, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Twenty Cent', 'Seated Liberty', '20C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'twenty cent seated liberty silver type united states mint coin coinage numismatics money', 0.01125, 'The Liberty Seated twenty-cent piece was the shortest-lived U.S. coin denomination. This century-old, 90% silver coin depicts 13 stars around Liberty, who sits on a rock with a Union Shield and a Liberty cap on a pole. The reverse shows an eagle with an olive branch and three arrows. Encircling the reverse are UNITED STATES OF AMERICA and TWENTY CENTS.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Quarter', 'Draped Bust', '25C', 'Silver', '0.8924', 'United States', 'U.S. Mint', 'draped bust quarter silver type united states mint coin coinage numismatics money', 0.014, 'Struck over 200 years ago, this silver Draped Bust quarter bears Robert Scot''s classic designs. The obverse shows 13 stars around Liberty with a plunging neckline, her long hair held by a ribbon. The reverse is the second design to appear on a U.S. quarter. It bears a heraldic eagle surrounded by UNITED STATES OF AMERICA and the denomination 20 c.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Capped Bust Quarter', 'Capped Bust', '25C', 'Silver', NULL, 'United States', 'U.S. Mint', 'capped bust quarter silver type united states mint coin coinage numismatics money', 0.014375, 'Struck around 200 years ago, this silver Capped Bust quarter bears John Reich''s classic Capped Bust design. It depicts Liberty in a cloth cap, surrounded by 13 stars and the date of issue. The reverse shows a banner with the motto E PLURIBUS UNUM above an eagle with Union Shield, an olive branch and three arrows. Encircling the eagle are 25 c. and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Seated Liberty Quarter', 'Seated Liberty', '25C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'seated liberty quarter silver type united states mint coin coinage numismatics money', 0.014375, 'Designed by Christian Gobrecht, this classic 90% silver quarter features Liberty seated on a rock surrounded by 13 stars. Her left hand holds a liberty cap atop a pole; her right, a shield inscribed LIBERTY. The reverse depicts an eagle on an olive branch holding three arrows.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Barber Quarter', 'Barber', '25C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'barber quarter liberty head silver type united states mint coin coinage numismatics money', 0.013125, 'Designed by Charles E. Barber, this 90% silver Liberty Head quarter (or Barber quarter) depicts Liberty wearing a laurel wreath and Phrygian cap with a band inscribed LIBERTY. The reverse bears an adaptation of the Great Seal of the United States.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Standing Liberty Quarter', 'Standing Liberty', '25C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'standing liberty quarter silver type united states mint coin coinage numismatics money', 0.013125, 'Considered one of America''s most beautiful coinage designs, this Standing Liberty quarter depicts Liberty standing in a gateway, holding an olive branch and uncovering a shield. The reverse features 13 stars surrounding a majestic eagle in flight.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Washington Quarter', 'Washington', '25C', NULL, NULL, 'United States', 'U.S. Mint', 'washington quarter united states mint coin coinage numismatics money', 0.013125, 'The obverse of this Washington quarter bears John Flanagan''s familiar portrait of America''s first president. Its reverse features his depiction of a proud eagle with spread wings, perched on a bundle of arrows above two olive branches.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('State Quarter', 'State', '25C', NULL, '0.9', 'United States', 'U.S. Mint', 'state statehood quarter united states mint coin coinage numismatics money', 0.013125, 'The Statehood quarters of 1999-2008 honored the 50 states in order of statehood, with reverse designs representing the featured state. John Flanagan''s original Washington portrait obverse was slightly modified to make room for the new, changing reverse designs.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('DC & US Territories Quarter', 'DC & US Territories', '25C', NULL, '0.9', 'United States', 'U.S. Mint', 'district columbia dc united states territories quarter mint coin coinage numismatics money', 0.013125, 'In 2009, the U.S. Mint followed its Statehood quarter program with six new quarters bearing reverse designs honoring the District of Columbia and the five U.S. territories. Each bore John Flanagan''s modified Washington portrait obverse.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('America the Beautiful Quarter', 'National Park', '25C', NULL, NULL, 'United States', 'U.S. Mint', 'national park quarter united states mint coin coinage numismatics money', 0.013125, 'The National Park quarters series honors a national park or historic site in each of the 50 states, the District of Columbia and the five U.S. territories. Each coin bears John Flanagan''s modified Washington portrait obverse and a special reverse design representing the featured site.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('American Women Quarter', 'American Women', '25C', NULL, '0.999', 'United States', 'U.S. Mint', 'american women quarter united states mint coin coinage numismatics money', 0.013125, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Semiquincentennial Quarter', 'Semiquincentennial', '25C', NULL, '0.999', 'United States', 'U.S. Mint', 'semiquincentennial quarter united states mint coin coinage numismatics money', 0.013125, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Half Dollar', 'Draped Bust', '50C', 'Silver', '0.8924', 'United States', 'U.S. Mint', 'draped bust half dollar silver type united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Capped Bust Half Dollar', 'Capped Bust', '50C', 'Silver', NULL, 'United States', 'U.S. Mint', 'capped bust half dollar silver type united states mint coin coinage numismatics money', 0.03, 'Struck over 180 years ago, this 89% silver Capped Bust half dollar bears John Reich''s classic design. It depicts Liberty with long, curling hair and a cloth cap, surrounded by 13 stars and the date. The reverse shows an American eagle with Union Shield, an olive branch and three arrows. A banner above the eagle reads E PLURIBUS UNUM, with 50 c. below and UNITED STATES OF AMERICA surrounding. Inscribed along the coin''s edge is FIFTY CENTS or HALF A DOLLAR.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Seated Liberty Half Dollar', 'Seated Liberty', '50C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'seated liberty half dollar silver type united states mint coin coinage numismatics money', 0.02625, 'This 90% silver Liberty Seated half dollar was struck over a century ago. Its classic obverse shows Liberty seated on a rock, holding a Union Shield, a ribbon inscribed LIBERTY and a Liberty cap atop a pole. 13 stars and the date surround her. The reverse depicts an American eagle with Union Shield, an olive branch and three arrows. Encircling the reverse are UNITED STATES OF AMERICA and HALF DOLLAR.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Barber Half Dollar', 'Barber', '50C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'barber half dollar liberty head silver type united states mint coin coinage numismatics money', 0.026875, 'This 90% silver Liberty Head half dollar (or Barber half dollar) bears Charles E. Barber''s classic depiction of Liberty wearing a laurel wreath and Phrygian cap held by a band inscribed LIBERTY. The reverse bears an adaptation of the Great Seal of the United States.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Liberty Walking Half Dollar', 'Liberty Walking', '50C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'liberty walking half dollar silver type united states mint coin coinage numismatics money', 0.026875, 'Designed by Adolph A. Weinman, this 90% silver Liberty Walking half dollar is one of America''s most beautiful coins. The obverse shows Liberty with arm outstretched, striding towards the dawn of a new day. The reverse depicts an eagle on a rocky crag, where a pine branch grows to symbolize strength.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Franklin Half Dollar', 'Franklin', '50C', 'Silver', '0.9', 'United States', 'U.S. Mint', 'franklin half dollar silver type united states mint coin coinage numismatics money', 0.0275, 'This Franklin half dollar is from America''s last circulating half dollar series struck entirely in 90% silver. Its obverse bears John R. Sinnock''s portrait of Benjamin Franklin, while the reverse depicts the Liberty Bell and a small eagle.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Kennedy Half Dollar', 'Kennedy', '50C', NULL, NULL, 'United States', 'U.S. Mint', 'kennedy half dollar united states mint coin coinage numismatics money', 0.026, 'This Kennedy half dollar honors America''s 35th president, John F. Kennedy. The obverse designed by Gilroy Roberts features a left-facing portrait of Kennedy, while Frank Gasparro''s reverse displays the presidential coat of arms.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Semiquincentennial Half Dollar', 'Semiquincentennial', '50C', NULL, '0.999', 'United States', 'U.S. Mint', 'semiquincentennial half dollar united states mint coin coinage numismatics money', 0.026, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Draped Bust Dollar', 'Draped Bust', '$1', 'Silver', '0.8924', 'United States', 'U.S. Mint', 'draped bust dollar silver type united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Seated Liberty Dollar', 'Seated Liberty', '$1', 'Silver', '0.9', 'United States', 'U.S. Mint', 'seated liberty dollar silver type united states mint coin coinage numismatics money', 0.05875, 'This century-old 90% silver Liberty Seated dollar bears Christian Gobrecht''s classic design. On the obverse, Liberty sits on a rock surrounded by 13 stars. She holds a Union Shield with banner that reads LIBERTY and a Liberty cap atop a pole. The reverse bears an American eagle with shield, olive branch and 3 arrows. Encircling the eagle are the denomination ONE DOL. and UNITED STATES OF AMERICA.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Trade Dollar', 'Trade', '$1', 'Silver', '0.9', 'United States', 'U.S. Mint', 'trade dollar silver type united states mint coin coinage numismatics money', 0.06, 'Designed by William Barber, this 90% silver Trade dollar was struck over a century ago for use in trade with Asia. Its obverse depicts 13 stars around Liberty, who sits on a bale of merchandise by the sea. Her right hand extends an olive branch; her left rests atop a sheaf of wheat and a ribbon inscribed LIBERTY. The reverse shows an American eagle holding three arrows and an olive branch. Below the eagle is 420 GRAINS, 900 FINE., while a banner above reads E PLURIBUS UNUM. UNITED STATES OF AMERICA and TRADE DOLLAR encircle the reverse.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Morgan Dollar', 'Morgan', '$1', 'Silver', '0.9', 'United States', 'U.S. Mint', 'morgan dollar silver type united states mint coin coinage numismatics money', 0.05875, 'This large 90% silver Morgan dollar bears George T. Morgan''s classic depiction of Liberty facing left in a Phrygian cap and a spray of leaves held by a ribbon inscribed LIBERTY. The reverse features an eagle clutching a branch and three arrows.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Morgan Dollar Toned', 'Morgan', '$1', 'Silver', '0.9', 'United States', 'U.S. Mint', 'morgan dollar silver type united states mint coin coinage numismatics money', 0.05875, 'This large 90% silver Morgan dollar bears George T. Morgan''s classic depiction of Liberty facing left in a Phrygian cap and a spray of leaves held by a ribbon inscribed LIBERTY. The reverse features an eagle clutching a branch and three arrows.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Peace Dollar', 'Peace', '$1', 'Silver', '0.9', 'United States', 'U.S. Mint', 'peace dollar silver type united states mint coin coinage numismatics money', 0.05875, 'Designed by Anthony de Francisci, this 90% silver Peace depicts a left-facing Liberty wearing a diadem of spikes. The reverse shows an eagle perched on a rock with a laurel branch above the word PEACE, facing the rays of a rising sun.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Eisenhower Dollar', 'Eisenhower', '$1', NULL, '0.4', 'United States', 'U.S. Mint', 'eisenhower ike dollar united states mint coin coinage numismatics money', 0.049, 'This large dollar coin depicts 34th President Dwight D. Eisenhower, with LIBERTY above and the date below. Next to the portrait is the motto IN GOD WE TRUST. Adapted from the Apollo 11 mission patch, the reverse shows an eagle landing on the moon''s surface, with the earth and E PLURIBUS UNUM above. Encircling the design are UNITED STATES OF AMERICA and ONE DOLLAR.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Susan B Anthony Dollar', 'Susan B Anthony', '$1', 'Copper-Nickel Clad', NULL, 'United States', 'U.S. Mint', 'susan anthony dollar sba united states mint coin coinage numismatics money', 0.018, 'This historic coin is from the America''s first small-size dollar series. Its obverse depicts women''s rights pioneer Susan B. Anthony. Surrounding her portrait are by 13 stars, LIBERTY, IN GOD WE TRUST and the date. Adapted from the Apollo 11 mission patch, the reverse shows an eagle landing on the moon with the earth in the background. Above the eagle are UNITED STATES OF AMERICA and E PLURIBUS UNUM, with ONE DOLLAR below.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Native American Dollar', 'Native American', '$1', 'Manganese-Brass', NULL, 'United States', 'U.S. Mint', 'native american sacagawea dollar united states mint coin coinage numismatics money', 0.018, 'This historic Sacagawea dollar honors Lewis and Clark''s young Shoshone guide. The obverse depicts Sacagawea holding her infant son, Jean Baptiste. LIBERTY is inscribed above the pair, with IN GOD WE TRUST on the left and the date at right. The reverse bears a soaring American eagle and E PLURIBUS UNUM. Encircling the reverse are 13 stars, UNITED STATES OF AMERICA and ONE DOLLAR.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Presidential Dollar', 'Presidential', '$1', 'Manganese-Brass', NULL, 'United States', 'U.S. Mint', 'presidential dollar united states mint coin coinage numismatics money', 0.018, 'Presidential dollars honor America''s presidents in order of service. Each obverse depicts the featured president, while a common reverse shows the Statue of Liberty and the denomination $1. Edge inscriptions bear each coin''s date, mint mark and the mottoes IN GOD WE TRUST and E PLURIBUS UNUM.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('American Innovation Dollar', 'American Innovation', '$1', 'Manganese-Brass', NULL, 'United States', 'U.S. Mint', 'american innovation dollar united states mint coin coinage numismatics money', 0.018, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold $1', NULL, '$1', 'Gold', '0.9', 'United States', 'U.S. Mint', 'gold dollar united states mint coin coinage numismatics money', 0.00375, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold $2.50 Quarter Eagle', NULL, '$2.50', 'Gold', NULL, 'United States', 'U.S. Mint', 'quarter eagle gold dollar united states mint coin coinage numismatics money', 0.009, 'This gold Classic Head quarter eagle was designed by William Kneass. Its obverse is based on John Reich''s classic image of Liberty with flowing hair held by a band inscribed LIBERTY. The date is below, with 13 stars surrounding. The reverse depicts UNITED STATES OF AMERICA and 2 1/2 D. around an American eagle with Union shield, olive branch and three arrows.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold $3', 'Indian Princess Head', '$3', 'Gold', '0.9', 'United States', 'U.S. Mint', 'gold dollar united states mint coin coinage numismatics money', 0.011, 'This Indian Princess three-dollar gold piece was designed by James B. Longacre. His classic obverse depicts Liberty in a crown of feathers, on which is a band inscribed LIBERTY. Surrounding her is the inscription UNITED STATES OF AMERICA. The reverse features a wreath of corn, wheat, cotton and tobacco around 3 DOLLARS and the date of issue.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold $5 Half Eagle', NULL, '$5', 'Gold', NULL, 'United States', 'U.S. Mint', 'half eagle gold dollar united states mint coin coinage numismatics money', 0.018, 'This gold Classic Head half eagle was designed by William Kneass. Its obverse is based on John Reich''s classic image of Liberty with flowing hair held by a band inscribed LIBERTY. The date is below, with 13 stars surrounding. The reverse depicts UNITED STATES OF AMERICA and 2 1/2 D. around an American eagle with Union shield, olive branch and three arrows.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold $10 Eagle', NULL, '$10', 'Gold', NULL, 'United States', 'U.S. Mint', 'eagle gold dollar united states mint coin coinage numismatics money', NULL, 'This gold Liberty Head eagle - also called a Coronet Head or Braided Hair eagle - bears Christian Gobrecht''s classic designs. The obverse shows 13 stars around Liberty, who wears a diadem and her long hair in a bun held by a string of beads. On the reverse, a proud American eagle with Union Shield holds an olive branch and three arrows. The denomination TEN D. and UNITED STATES OF AMERICA encircle the reverse.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold $20 Double Eagle', NULL, '$20', 'Gold', '0.9', 'United States', 'U.S. Mint', 'double eagle gold dollar united states mint coin coinage numismatics money', 0.074, 'Struck in 90% gold over a century ago, this gold Liberty Head double eagle is larger than a Kennedy half dollar and weighs over 1 troy ounce. It bears James B. Longacre''s classic design. The obverse shows Liberty in a diadem with her long hair up in a bun, surrounded by 13 stars and the date of issue. The reverse bears a stylish heraldic eagle beneath a circle of 13 stars and series of rays. The denomination and UNITED STATES OF AMERICA encircle the design.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Fractional Pioneer Gold', 'California Fractional', NULL, 'Gold', NULL, 'United States', NULL, 'fractional pioneer gold united states mint coin coinage numismatics money', NULL, 'Struck during the California gold rush, this privately minted gold piece helped ease the shortage of small-denomination coins. Containing up to 85% of face value in real, California gold, these unique and historic issues quickly became collector favorites.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Colonial', NULL, NULL, NULL, NULL, NULL, NULL, 'colonial mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Post Colonial', NULL, NULL, NULL, NULL, NULL, NULL, 'post colonial mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('U.S. Philippine Coin', 'U.S. Philippines', NULL, NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Error Coin', NULL, NULL, NULL, NULL, 'United States', 'U.S. Mint', 'error mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Classic Silver Commemorative', 'Commemorative', NULL, 'Silver', '0.9', 'United States', 'U.S. Mint', 'classic silver commemorative united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Modern Silver/Clad Commemorative', 'Commemorative', NULL, NULL, NULL, 'United States', 'U.S. Mint', 'modern silver clad commemorative united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Classic Gold Commemorative', 'Commemorative', NULL, 'Gold', '0.9', 'United States', 'U.S. Mint', 'classic gold commemorative united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Modern Gold Commemorative', 'Commemorative', NULL, 'Gold', '0.9', 'United States', 'U.S. Mint', 'modern gold commemorative united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mint Set', NULL, NULL, NULL, NULL, 'United States', 'U.S. Mint', 'mint set uncirculated united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Proof Set', NULL, NULL, NULL, NULL, 'United States', 'U.S. Mint', 'mint proof set united states mint coin coinage numismatics money', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Small Cent Mixed Lot', NULL, '1C', NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Nickel Mixed Lot', NULL, '5C', NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Half Dollar Mixed Lot', NULL, '50C', NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Dollar Mixed Lot', NULL, '$1', NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Gold Mixed Lot', NULL, NULL, 'Gold', NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Coin Mixed Lot', NULL, NULL, NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Rolls', NULL, NULL, NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other US Coin', NULL, NULL, NULL, NULL, 'United States', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Silver Bullion Coin', NULL, NULL, 'Silver', NULL, NULL, NULL, 'silver bullion mint coin coinage numismatics money precious metal investment', NULL, 'Struck in 1 troy ounce of 99.9% pure silver, this hefty American silver eagle bears the series'' original designs. The obverse bears Adolph A. Weinman''s beloved image of Liberty striding to greet the dawn of a new day. She wears a billowing gown of stars and stripes, and carries oak and laurel branches for military and civic glory. John Mercanti''s striking reverse shows a heraldic eagle under a triangle of 13 stars. Encircling the reverse are UNITED STATES OF AMERICA and 1 OZ. FINE SILVER-ONE DOLLAR.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Silver Bar or Round', NULL, NULL, 'Silver', NULL, NULL, NULL, 'silver bullion bar round mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Silver Bullion', NULL, NULL, 'Silver', NULL, NULL, NULL, 'silver bullion bar round mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold Bullion Coin', NULL, NULL, 'Gold', NULL, NULL, NULL, 'gold bullion mint coin coinage numismatics money precious metal investment', NULL, 'This hefty $5 American Buffalo gold bullion coin contains 1/10 ounce of 99.99% pure gold. Only issued in 2008, it''s from the first 24K gold coin series produced by the U.S. Mint. The coin bears James E. Fraser''s iconic Buffalo nickel (or Indian Head) nickel designs. The obverse depicts a proud Native American, while the reverse shows an American bison.', NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold Bar or Round', NULL, NULL, 'Gold', NULL, NULL, NULL, 'gold bullion bar round mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold Leaf or Flake', NULL, NULL, 'Gold', NULL, NULL, NULL, 'gold bullion leaf flake mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gold Nugget', NULL, NULL, 'Gold', NULL, NULL, NULL, 'gold bullion nugget mint coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Platinum Bullion Coin', NULL, NULL, 'Platinum', NULL, NULL, NULL, 'platinum bullion mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Platinum Bar or Round', NULL, NULL, 'Platinum', NULL, NULL, NULL, 'platinum bullion bar round mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Palladium Bullion Coin', NULL, NULL, 'Palladium', NULL, NULL, NULL, 'palladium bullion mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Titanium Bar or Round', NULL, NULL, 'Titanium', NULL, NULL, NULL, 'titanium bar round mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Copper Bar or Round', NULL, NULL, 'Copper', NULL, NULL, NULL, 'copper bar round mint coin coinage numismatics money precious metal investment', NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Bullion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size Federal Reserve Bank Note', 'Large Size Federal Reserve Bank Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size Federal Reserve Note', 'Large Size Federal Reserve Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size Gold Certificate', 'Large Size Gold Certificate Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size Legal Tender Note', 'Large Size Legal Tender Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size National Banknote', 'Large Size National Banknote', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size Silver Certificate', 'Large Size Silver Certificate Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Large Size Paper Money Mixed Lot', NULL, NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size Federal Reserve Bank Note', 'Small Size Federal Reserve Bank Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size Federal Reserve Note', 'Small Size Federal Reserve Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size Gold Certificate', 'Small Size Gold Certificate Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size Legal Tender Note', 'Small Size Legal Tender Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size National Banknote', 'Small Size National Banknote', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size Silver Certificate', 'Small Size Silver Certificate Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size WWII Emergency Note', NULL, NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Small Size Paper Money Mixed Lot', NULL, NULL, NULL, NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Colonial Currency', NULL, NULL, 'Paper', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Confederate Currency', 'Confederate Note', NULL, 'Paper', NULL, 'United States', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Fractional Currency', 'Fractional Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Military Payment Certificate', 'Military Payment Certificate', NULL, 'Paper', NULL, 'United States', 'Department of War', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Obsolete Currency', NULL, NULL, 'Paper', NULL, 'United States', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Obsolete Bank Check', 'Obsolete Bank Check', NULL, 'Paper', NULL, 'United States', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Treasury Note', 'Treasury Note', NULL, 'Paper', NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other US Paper Money', NULL, NULL, NULL, NULL, 'United States', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Paper Money Mixed Lot', NULL, NULL, NULL, NULL, 'United States', 'The Bureau of Engraving & Printing', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Australia Collection', NULL, NULL, NULL, NULL, 'Australia', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Australia Commemorative', NULL, NULL, NULL, NULL, 'Australia', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Australia Proof Set', 'Australian Proof Set', NULL, NULL, NULL, 'Australia', 'Royal Australian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Austria Coin', NULL, NULL, NULL, NULL, 'Austria', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('British India Coin', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Cameroon Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Cayman Islands Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Large Cent', NULL, '1C', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Small Cent', NULL, '1C', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Five Cent Silver', NULL, '5C', 'Silver', '0.925', 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Five Cent', NULL, '5C', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Ten Cent', NULL, '10C', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Twenty Cent', NULL, '20C', 'Silver', '0.925', 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Twenty Five Cent', NULL, '25C', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Fifty Cent', NULL, '50C', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Dollar', NULL, '$1', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Two Dollar', NULL, '$2', NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Commemorative', NULL, NULL, NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Mint Set', 'Canadian Mint Set', NULL, NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Proof Set', 'Canadian Proof Set', NULL, NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Specimen Set', 'Canadian Specimen Set', NULL, NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Pre-Confederation Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Token', NULL, NULL, NULL, NULL, 'Canada', 'Royal Canadian Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Canada Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Canada Mixed Lot', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Cook Islands Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('East Germany Coin', NULL, NULL, NULL, NULL, 'Germany', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Egypt Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Fiji Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('German States Coin', NULL, NULL, NULL, NULL, 'Germany', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('German Empire Coin', NULL, NULL, NULL, NULL, 'Germany', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Germany Weimar Republic Coin', NULL, NULL, NULL, NULL, 'Germany', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Germany Third Reich Coin', NULL, NULL, NULL, NULL, 'Germany', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Germany West & Unified Coin', NULL, NULL, NULL, NULL, 'Germany', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ghana Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Hawaiian Coin', 'Hawaiian', NULL, NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Indonesia Coin', NULL, NULL, NULL, NULL, 'Indonesia', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Isle of Man Coin', NULL, NULL, NULL, NULL, 'Isle of Man', 'Pobjoy Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Italy Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Liberia Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Maldives Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Malta Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico Colonial Coin', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico War of Independence Coin', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico Empire of Iturbide Coin', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico First Republic Coin', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico Empire of Maximilian Coin', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico Second Republic Coin', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico Modern Coin', NULL, NULL, NULL, NULL, 'Mexico', 'Mexican Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Mexico Mixed Lot', NULL, NULL, NULL, NULL, 'Mexico', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Nepal Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Netherlands Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Russia Empire Coin', NULL, NULL, NULL, NULL, 'Russia', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Russia USSR Coin', NULL, NULL, NULL, NULL, 'Russia', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Russia Federation Coin', NULL, NULL, NULL, NULL, 'Russia', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Solomon Islands Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Spanish Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Sweden Coin', NULL, NULL, NULL, NULL, 'Sweden', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Swiss Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Tuvalu Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('UK / Great Britain Penny', NULL, NULL, NULL, NULL, 'United Kingdom', 'The Royal Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('UK / Great Britain Threepence', NULL, NULL, NULL, NULL, 'United Kingdom', 'The Royal Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('UK / Great Britain Sixpence', NULL, NULL, NULL, NULL, 'United Kingdom', 'The Royal Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('UK / Great Britain Commemorative', NULL, NULL, NULL, NULL, 'United Kingdom', 'The Royal Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other UK / Great Britain Coin', NULL, NULL, NULL, NULL, 'United Kingdom', 'The Royal Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Vanuatu Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Asia Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('World Coin Mixed Lot', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('China Paper Money', NULL, NULL, 'Paper', NULL, 'China', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('France Allied Military Currency', NULL, NULL, 'Paper', NULL, 'France', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Italy Allied Military Currency', NULL, NULL, 'Paper', NULL, 'Italy', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Japan Allied Military Currency', NULL, NULL, 'Paper', NULL, 'Japan', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ancient Byzantine Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ancient Gaulish Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ancient Greek Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ancient Roman Imperial Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ancient Roman Provincial Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Ancient Roman Republic Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Ancient Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Car Wash Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Civil War Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Good Luck Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Hard Times Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Love Token', NULL, NULL, NULL, NULL, 'United States', 'U.S. Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Parking Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Recovery Program Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Tax Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Transit Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('US Trade Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Token', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Hobo Nickel', 'Hobo Buffalo', '5C', 'Copper-Nickel', NULL, 'United States', 'U.S. Mint', NULL, 0.01125, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Medal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Native American Coin', NULL, NULL, NULL, NULL, 'United States', 'Native American Mint', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Snuff Opium Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('So-Called Dollar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Exonumia', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Advent Calendar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Challenge Coin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Christmas Nativity Items', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Christmas Tree Ornaments', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Disney Dollars', NULL, NULL, 'Paper', NULL, NULL, 'Disney', NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Gift Box', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Keychain', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Necklace', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Novelty US Paper Money', NULL, NULL, 'Paper', NULL, 'United States', NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Historical Memorabilia', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Star Trek Collectible', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Palm Stone', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Postcards', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Role Playing Game Accessories', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('United States Postage Stamp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Wristwatches', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Coin Album', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Other Coin & Money Supplies', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO LSCDEVLIBP.SBLCATDFT
  (category_name, coin_type, denomination, composition, fineness, country,
   brand, search_terms, weight_lb, copy_description, collector_note)
  VALUES ('Earring', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
