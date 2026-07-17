-- ----------------------------------------------------------
-- MDB Tools - A library for reading MS Access database files
-- Copyright (C) 2000-2011 Brian Bruns and others.
-- Files in libmdb are licensed under LGPL and the utilities under
-- the GPL, see COPYING.LIB and COPYING files respectively.
-- Check out http://mdbtools.sourceforge.net
-- ----------------------------------------------------------

-- That file uses encoding UTF-8

CREATE TABLE [Area Table]
 (
	[Area Code]			Text (4), 
	[Area]			Text (25)
);

CREATE TABLE [Area Type]
 (
	[Area Type]			Text (15), 
	[Area]			Text (16), 
	[Area Code]			Text (3)
);

CREATE TABLE [Inventory Data Entry Table]
 (
	[Inv DE Number]			Text (3), 
	[Last Name]			Text (15), 
	[First Name]			Text (10), 
	[Phone Number]			Text (50), 
	[Start Date]			DateTime, 
	[Comment]			Memo/Hyperlink (255), 
	[Picture]			OLE (255), 
	[Web Page]			Memo/Hyperlink (255)
);

CREATE TABLE [Requested Material Table]
 (
	[Requested Number]			Long Integer, 
	[Name]			Text (50), 
	[Item #]			Text (16), 
	[Loc]			Text (3), 
	[Coin Date]			Text (10), 
	[Description]			Text (50), 
	[Quantity]			Text (7), 
	[Cost]			Currency, 
	[Retail]			Currency, 
	[Area Type]			Text (20), 
	[Area Code]			Text (2), 
	[Date]			DateTime, 
	[Inv DE Number]			Text (4), 
	[Returned]			Boolean NOT NULL, 
	[Date Returned]			DateTime
);

CREATE TABLE [Requisitioner Table]
 (
	[Name]			Text (30), 
	[Dept]			Text (30), 
	[Area Code]			Text (5), 
	[Inv DE Number]			Text (5)
);

CREATE TABLE [Switchboard Items]
 (
	[SwitchboardID]			Long Integer, 
	[ItemNumber]			Integer, 
	[ItemText]			Text (255), 
	[Command]			Integer, 
	[Argument]			Text (255)
);


