-- Query: ~sq_fRequested Material Subform
SELECT [Requested Material Table].Name,[Requested Material Table].[Item #],[Requested Material Table].Quantity,[Requested Material Table].Retail,[Requisitioner Table].[Inv DE Number] FROM [Requisitioner Table],[Requested Material Table] 
