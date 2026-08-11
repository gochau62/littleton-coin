Attribute VB_Name = "Form_FootMaintenance"
Attribute VB_Base = "0{092FB904-BC25-11D2-8A92-00105AA350F4}"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Attribute VB_TemplateDerived = False
Attribute VB_Customizable = False
Option Compare Database
Option Explicit

Public strSelectSKy As String
Public strMode As String
Public dbFoot As Database
Public strSQL As String

Private Sub Combo1_LostFocus()

LoadText

End Sub

Private Sub Command1_Click()

' command button SAVE

Dim strSCFSKy As String
Dim strSCFTXT As String
Dim intSCFLNO As Integer
Dim strNewLine As String
Dim intStart As Integer
Dim intLine As Integer
Dim intPosition As Integer
Dim strTextLine As String
Dim intLength As Integer
Dim rs As Recordset

'save the text

DoCmd.Hourglass True

strNewLine = Chr(13)

Text1.SetFocus
strTextLine = Text1.Text


Combo1.SetFocus

strSCFSKy = Combo1.Text

Select Case strMode

    Case "i"    'insert a new footer text
        
        intStart = 1
        intSCFLNO = 1

        'insert the text
        
        intLength = Len(strTextLine)
        
        For intPosition = 1 To intLength
            If Mid(strTextLine, intPosition, 1) = strNewLine Then
                strSCFTXT = Mid(strTextLine, intStart, (intPosition - intStart))
                
                strSQL = "insert into lscprdlib_ivscfootp ([scfsky],scflno,[scftxt]) " & _
                        "values(" & 1 & "," & _
                        intSCFLNO & ",'" & _
                        strSCFTXT & "')"
                dbFoot.Execute (strSQL)
                
                intStart = intPosition + 2
                intSCFLNO = intSCFLNO + 1
            Else
            ' if this is the last char and its not an new line then make it
                If intPosition = intLength Then
                    strSCFTXT = Mid(strTextLine, intStart, (intPosition - intStart + 1))
                
                    strSQL = "insert into lscprdlib_ivscfootp (scfsky,scflno,scftxt) " & _
                            "values(" & 1 & "," & _
                            intSCFLNO & ",'" & _
                            strSCFTXT & "')"
                    dbFoot.Execute (strSQL)
                
                    intStart = intPosition + 2
                    intSCFLNO = intSCFLNO + 1
               End If
                
                
            End If

        Next
        
        Combo1.SetFocus
        
    Case "u"    'update the header text
        
        intStart = 1
        intSCFLNO = 1

        'update the text
        
        intLength = Len(strTextLine)
        
        For intPosition = 1 To intLength
            If Mid(strTextLine, intPosition, 1) = strNewLine Then
                strSCFTXT = Mid(strTextLine, intStart, (intPosition - intStart))
                
                strSQL = "update lscprdlib_ivscfootp set " & _
                        "[scftxt] ='" & strSCFTXT & "' " & _
                        "where [scfsky]= 1  " & _
                        "and scflno = " & intSCFLNO
                        
                dbFoot.Execute (strSQL)
                
                intStart = intPosition + 2
                intSCFLNO = intSCFLNO + 1
            Else
            ' if this is the last char and its not an new line then make it
                If intPosition = intLength Then
                    strSCFTXT = Mid(strTextLine, intStart, (intPosition - intStart + 1))
                
                strSQL = "update lscprdlib_ivscfootp set " & _
                        "[scftxt] ='" & strSCFTXT & "' " & _
                        "where [scfsky]= 1  " & _
                        "and scflno = " & intSCFLNO
                    dbFoot.Execute (strSQL)
                
                    intStart = intPosition + 2
                    intSCFLNO = intSCFLNO + 1
               End If
                
                
            End If

        Next
        
        Combo1.SetFocus

End Select
    


DoCmd.Hourglass False

End Sub

Private Function CheckSku(strSKU As String) As Boolean

'validate the sku (it must exist in the database)

Dim rs As Recordset

    CheckSku = True 'set default to true (all ok)
    
    Set dbFoot = CurrentDb
    
    Combo1.SetFocus
    
    If strSKU = "" Then
        MsgBox "A SKU Must be Entered"
        CheckSku = False
        Exit Function
    End If
    
    strSQL = "SELECT Count(*) AS [RECCOUNT] from LSCPRDLIB_ITMMSTL1 where [IISKU#]='" & _
            strSKU & "'"
    
    Set rs = dbFoot.OpenRecordset(strSQL)
    
    If rs.Fields("RECCOUNT") = 0 Then
        MsgBox "SKU Not in Master File"
        CheckSku = False
    End If
        
End Function

Private Sub LoadText()

'load the header text box with the text for the sku
'check for an insert or update

Dim rs As Recordset
Dim strTextLine As String
Dim err As Error

On Error GoTo dsperr

Combo1.SetFocus
strSelectSKy = Combo1.Text
    
Set dbFoot = CurrentDb

If strSelectSKy <> "" Then

    strSQL = "select count(*) as [reccount] from LSCPRDLIB_IVSCfootP where " & _
            "LSCPRDLIB_IVSCfootP.SCfSKy=1"
    Set rs = dbFoot.OpenRecordset(strSQL)

    If rs.Fields("reccount") = 0 Then
        strMode = "i"
    Else
        strMode = "u"
    End If
 
    Select Case strMode
    
        Case "u"
    
            strSQL = "select [scftxt],scflno from LSCPRDLIB_ivscfootp where " & _
                        "scfsky=" & strSelectSKy & _
                " ORDER BY LSCPRDLIB_IVSCFOOTP.SCFLNO"
            
            Set rs = dbFoot.OpenRecordset(strSQL)

            Do While rs.EOF = False
                strTextLine = strTextLine & rs.Fields("scftxt") & vbCrLf
                rs.MoveNext
            Loop
    
            Text1.SetFocus

            Text1.Text = strTextLine
        Case "i"
    
            Text1.SetFocus

            Text1.Text = ""

    End Select

End If

Exit Sub

dsperr:

For Each err In Errors
    MsgBox err.Description
Next



End Sub
