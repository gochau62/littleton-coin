-- Query: Inventory DE and Course Offerings
SELECT [Inventory Data Entry Table].[Inv DE Number],[Course Offerings Table].[Course Number],[Course Offerings Table].[Hours Spent],[Total Hours]-[Hours Spent] FROM [Inventory Data Entry Table],[Course Offerings Table] 
