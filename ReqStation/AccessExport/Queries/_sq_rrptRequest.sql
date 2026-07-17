-- Query: ~sq_rrptRequest
SELECT ReqMaterial.*,IIf([rush]=0,"No","Yes"),[employee tbl].FName,[employee tbl].LName FROM [ReqMaterial],[employee tbl] 
