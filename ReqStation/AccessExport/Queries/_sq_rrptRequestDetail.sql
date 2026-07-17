-- Query: ~sq_rrptRequestDetail
SELECT ReqMaterialDetails.*,[quantity]*[cost],[quantity]*[retail] FROM [ReqMaterialDetails] WHERE (((ReqMaterialDetails.returned)=0)) 
