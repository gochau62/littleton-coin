Attribute VB_Name = "Form_BodyMaintenance"
Attribute VB_Base = "0{4F41407B-AC29-11D4-99DD-00A0C90825F8}"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Attribute VB_TemplateDerived = False
Attribute VB_Customizable = False

Option Compare Database
Option Explicit
Public strSelectSku As String
Public strMode As String
Public dbHead As Database
Public strSQL As String

Private Sub Combo1_LostFocus()

'validate the sku

Combo1.SetFocus
strSelectSku = UCase(Combo1.Text)
Combo1.Text = strSelectSku

If strSelectSku <> "" Then
    If CheckSku(strSelectSku) = False Then
        Exit Sub
    End If

'load the text

LoadText

End If

End Sub

Private Sub Command1_Click()

' command button SAVE

Dim strSCBSKU As String
Dim strSCBTxt As String
Dim intSCBLno As Integer
Dim strSCBSRK As String
Dim strNewLine As String
Dim intStart As Integer
Dim intLine As Integer
Dim intPosition As Integer
Dim strTextLine As String
Dim strTextLine2 As String
Dim intLength As Integer
Dim rs As Recordset

'save the text

DoCmd.Hourglass True

strNewLine = Chr(13)

Text9.SetFocus
strTextLine = Text9.Text

Text11.SetFocus
strTextLine2 = Text11.Text

Text36.SetFocus
strSCBSRK = Text36.Text

Combo1.SetFocus
strSCBSKU = Combo1.Text

'1st check for a valid SKU

If Not CheckSku(strSCBSKU) Then
    Exit Sub
End If

'determine the mode

DetermineMode (strSCBSKU)

Select Case strMode

    Case "i"    'insert a new story card
        
        intStart = 1
        intSCBLno = 1

        'side 1
        
        intLength = Len(strTextLine)
        
        For intPosition = 1 To intLength
            If Mid(strTextLine, intPosition, 1) = strNewLine Then
                strSCBTxt = Mid(strTextLine, intStart, (intPosition - intStart))
                
                If intSCBLno <= 11 Then 'only insert the max lines (11)
                    strSQL = "insert into lscprdlib_ivscbodyp ([scbsku],scblno,[scbtxt],[scbsrk]) " & _
                            "values('" & strSCBSKU & "'," & _
                            intSCBLno & ",""" & _
                            strSCBTxt & """,""" & _
                            strSCBSRK & """)"
                    dbHead.Execute (strSQL)
                Else
                    MsgBox "The maximum number of lines (11) for side 1 has been entered" & _
                            vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                End If
                
                intStart = intPosition + 2
                intSCBLno = intSCBLno + 1
            Else
            ' if this is the last char and its not an new line then make it
                If intPosition = intLength Then
                    strSCBTxt = Mid(strTextLine, intStart, (intPosition - intStart + 1))
                
                If intSCBLno <= 11 Then 'only insert the max lines (11)
                
                    strSQL = "insert into lscprdlib_ivscbodyp (scbsku,scblno,scbtxt,scbsrk) " & _
                            "values('" & strSCBSKU & "'," & _
                            intSCBLno & ",""" & _
                            strSCBTxt & """,""" & _
                            strSCBSRK & """)"
                            
                    dbHead.Execute (strSQL)
                 Else
                    MsgBox "The maximum number of lines (11) for side 1 has been entered" & _
                            vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                End If
               
                intStart = intPosition + 2
                intSCBLno = intSCBLno + 1
               End If
                
                
            End If

        Next
        
        'side 2
        
        intStart = 1
        intSCBLno = 13

        intLength = Len(strTextLine2)
        
        For intPosition = 1 To intLength
            If Mid(strTextLine2, intPosition, 1) = strNewLine Then
                strSCBTxt = Mid(strTextLine2, intStart, (intPosition - intStart))
                
                If intSCBLno <= 21 Then 'only insert the max lines (20)
                               
                    strSQL = "insert into lscprdlib_ivscbodyp ([scbsku],scblno,[scbtxt],[scbsrk]) " & _
                            "values('" & strSCBSKU & "'," & _
                            intSCBLno & ",""" & _
                            strSCBTxt & """,""" & _
                            strSCBSRK & """)"
                    dbHead.Execute (strSQL)
                  Else
                    MsgBox "The maximum number of lines (9) for side 2 has been entered" & _
                            vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                End If
               
                intStart = intPosition + 2
                intSCBLno = intSCBLno + 1
            Else
            ' if this is the last char and its not an new line then make it
                If intPosition = intLength Then
                    strSCBTxt = Mid(strTextLine2, intStart, (intPosition - intStart + 1))
                
                If intSCBLno <= 21 Then 'only insert the max lines (20)
                
                    strSQL = "insert into lscprdlib_ivscbodyp (scbsku,scblno,scbtxt,scbsrk) " & _
                            "values('" & strSCBSKU & "'," & _
                            intSCBLno & ",""" & _
                            strSCBTxt & """,""" & _
                            strSCBSRK & """)"

                    dbHead.Execute (strSQL)
                  Else
                    MsgBox "The maximum number of lines (9) for side 2 has been entered" & _
                            vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                End If
                
                intStart = intPosition + 2
                intSCBLno = intSCBLno + 1
                
               End If
               
            End If

        Next
        Combo1.Requery
        
        Combo1.SetFocus
        
    Case "u"    'update the story card text
        
        intStart = 1
        intSCBLno = 1

        'side 1
        
        intLength = Len(strTextLine)
        
        For intPosition = 1 To intLength
            If Mid(strTextLine, intPosition, 1) = strNewLine Then
                strSCBTxt = Mid(strTextLine, intStart, (intPosition - intStart))
                
                If CheckLineMode(strSCBSKU, intSCBLno) = True Then
                
                    strSQL = "update lscprdlib_ivscbodyp set " & _
                            "[scbtxt] =""" & strSCBTxt & """," & _
                            "[scbsrk] =""" & strSCBSRK & """ " & _
                            "where [scbsku]='" & strSCBSKU & "' " & _
                            "and scblno =" & intSCBLno
                    dbHead.Execute (strSQL)
                Else
                
                    If intSCBLno <= 11 Then 'only insert the max lines (11)
                
                        strSQL = "insert into lscprdlib_ivscbodyp (scbsku,scblno,scbtxt,scbsrk) " & _
                                "values('" & strSCBSKU & "'," & _
                                intSCBLno & ",""" & _
                                strSCBTxt & """,""" & _
                                strSCBSRK & """)"
                        dbHead.Execute (strSQL)
                    Else
                        MsgBox "The maximum number of lines (11) for side 1 has been entered" & _
                                vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                    End If
               End If
                intStart = intPosition + 2
                intSCBLno = intSCBLno + 1
            Else
            ' if this is the last char and its not an new line then make it
                If intPosition = intLength Then
                    strSCBTxt = Mid(strTextLine, intStart, (intPosition - intStart + 1))
                
                If CheckLineMode(strSCBSKU, intSCBLno) = True Then
                
                    strSQL = "update lscprdlib_ivscbodyp set " & _
                            "[scbtxt] =""" & strSCBTxt & """," & _
                            "[scbsrk] =""" & strSCBSRK & """ " & _
                            "where [scbsku]='" & strSCBSKU & "' " & _
                            "and scblno =" & intSCBLno
                        dbHead.Execute (strSQL)
                Else
                    If intSCBLno <= 11 Then 'only insert the max lines (11)
                    
                        strSQL = "insert into lscprdlib_ivscbodyp (scbsku,scblno,scbtxt,scbsrk) " & _
                                "values('" & strSCBSKU & "'," & _
                                intSCBLno & ",""" & _
                                strSCBTxt & """,""" & _
                                strSCBSRK & """)"
                        dbHead.Execute (strSQL)
                    Else
                        MsgBox "The maximum number of lines (11) for side 1 has been entered" & _
                                vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                    End If
               End If
                        
                
                    intStart = intPosition + 2
                    intSCBLno = intSCBLno + 1
               End If
                
                
            End If

        Next

        'side 2
        
        intStart = 1
        intSCBLno = 13
        intLength = Len(strTextLine2)
        
        For intPosition = 1 To intLength
            If Mid(strTextLine2, intPosition, 1) = strNewLine Then
                strSCBTxt = Mid(strTextLine2, intStart, (intPosition - intStart))
                
                If CheckLineMode(strSCBSKU, intSCBLno) = True Then
                
                strSQL = "update lscprdlib_ivscbodyp set " & _
                        "[scbtxt] =""" & strSCBTxt & """," & _
                        "[scbsrk] =""" & strSCBSRK & """ " & _
                        "where [scbsku]='" & strSCBSKU & "' " & _
                        "and scblno =" & intSCBLno
                    dbHead.Execute (strSQL)
                Else
                
                If intSCBLno <= 21 Then 'only insert the max lines (20)
                
                    strSQL = "insert into lscprdlib_ivscbodyp (scbsku,scblno,scbtxt,scbsrk) " & _
                            "values('" & strSCBSKU & "'," & _
                            intSCBLno & ",""" & _
                            strSCBTxt & """,""" & _
                            strSCBSRK & """)"
                    dbHead.Execute (strSQL)
                  Else
                    MsgBox "The maximum number of lines (9) for side 2 has been entered" & _
                            vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                End If
               End If

                
                intStart = intPosition + 2
                intSCBLno = intSCBLno + 1
            Else
            ' if this is the last char and its not an new line then make it
                If intPosition = intLength Then
                    strSCBTxt = Mid(strTextLine2, intStart, (intPosition - intStart + 1))
                
                If CheckLineMode(strSCBSKU, intSCBLno) = True Then
                    strSQL = "update lscprdlib_ivscbodyp set " & _
                            "[scbtxt] =""" & strSCBTxt & """," & _
                            "[scbsrk] =""" & strSCBSRK & """ " & _
                            "where [scbsku]='" & strSCBSKU & "' " & _
                            "and scblno =" & intSCBLno
                        dbHead.Execute (strSQL)
                Else
                 If intSCBLno <= 21 Then 'only insert the max lines (20)
                    strSQL = "insert into lscprdlib_ivscbodyp (scbsku,scblno,scbtxt,scbsrk) " & _
                            "values('" & strSCBSKU & "'," & _
                            intSCBLno & ",""" & _
                            strSCBTxt & """,""" & _
                            strSCBSRK & """)"
                        dbHead.Execute (strSQL)
                  Else
                    MsgBox "The maximum number of lines (9) for side 2 has been entered" & _
                            vbCrLf & "The text - '" & strSCBTxt & "' - will not be added"
                End If
               End If
                        
                
                    intStart = intPosition + 2
                    intSCBLno = intSCBLno + 1
               End If
                
                
            End If

        Next
        
        'clear any blank lines
        
        If intSCBLno < 21 Then
            For intLine = intSCBLno To 21
                If CheckLineMode(strSCBSKU, intLine) = True Then
                    Call UpdateBlank(strSCBSKU, intLine)
                End If
            Next
        End If
        
        Combo1.SetFocus

End Select

DoCmd.Hourglass False

End Sub

Private Sub Form_Load()

Text23.SetFocus
Text23.Text = "1" & vbCrLf & "2" & vbCrLf & "3" & vbCrLf & "4" & vbCrLf & "5" & vbCrLf & "6" & vbCrLf & "7" & vbCrLf & "8" & vbCrLf & "9" & vbCrLf & "0" & vbCrLf & "1" & vbCrLf & "2"

Text21.SetFocus
Text21.Text = "1" & vbCrLf & "2" & vbCrLf & "3" & vbCrLf & "4" & vbCrLf & "5" & vbCrLf & "6" & vbCrLf & "7" & vbCrLf & "8" & vbCrLf & "9" & vbCrLf & "0"

Text25.SetFocus
Text25.Text = "1" & vbCrLf & "2"

    Combo1.SetFocus
End Sub

Private Function CheckSku(strSKU As String) As Boolean

'validate the sku (it must exist in the database)

Dim rs As Recordset

    CheckSku = True 'set default to true (all ok)
    
    Set dbHead = CurrentDb
    DBEngine.LoginTimeout = 1200
    
    If strSKU <> "" Then
    
        strSQL = "SELECT Count(*) AS [RECCOUNT] from LSCPRDLIB_ITMMSTL1 where [IISKU#]='" & _
                strSKU & "'"
    
        Set rs = dbHead.OpenRecordset(strSQL)
    
        If rs.Fields("RECCOUNT") = 0 Then
            MsgBox "SKU Not in Master File"
            Combo1.SetFocus
            CheckSku = False
            Exit Function
        End If
    Else
        CheckSku = False
        Exit Function
    End If

End Function
Private Sub LoadText()

'load the body text box with the text for the sku
'determine if an insert or update

Dim rs As Recordset
Dim strTextLine As String

Combo1.SetFocus
strSelectSku = Combo1.Text

'get the item description

strSQL = "select iidesc from LSCPRDLIB_ITMMSTL1 where [IISKU#]='" & _
            strSelectSku & "'"
            
Set rs = dbHead.OpenRecordset(strSQL)

Text31.SetFocus
Text31.Text = rs.Fields("iidesc")

'determine the mode

DetermineMode (strSelectSku)

'get the footer information

strTextLine = ""
        
strSQL = "select [scftxt] from LSCPRDLIB_ivscfootp where " & _
        "scfsky=1" & _
        " ORDER BY LSCprdLIB_IVSCFOOTP.SCFLNO"
            
Set rs = dbHead.OpenRecordset(strSQL)

Do While rs.EOF = False
    strTextLine = strTextLine & Trim(rs.Fields("scftxt")) & vbCrLf
    rs.MoveNext
Loop
    
Text13.SetFocus
Text13.Text = strTextLine

'get the story card text

strTextLine = ""

Select Case strMode
    
    Case "u"    'update mode
    
    'side 1
    
    strSQL = "select scbtxt,scbsrk from LSCPRDLIB_ivscbodyp where " & _
            "scbsku='" & strSelectSku & "' " & _
            "and scblno <= 12 " & _
            "order by LSCPRDLIB_ivscbodyP.scblno"
            
   Set rs = dbHead.OpenRecordset(strSQL)

    If rs.EOF = False Then
        Text36.SetFocus
        Text36.Text = rs.Fields("scbsrk")
    End If
    
    Do While rs.EOF = False
        strTextLine = strTextLine & Trim(rs.Fields("scbtxt")) & vbCrLf
        rs.MoveNext
    Loop
    
    Text9.SetFocus
    Text9.Text = strTextLine
        
    'side 2
    
    strTextLine = ""
    
    strSQL = "select scbtxt,scbsrk from LSCPRDLIB_ivscbodyp where " & _
            "scbsku='" & strSelectSku & "' " & _
            "and scblno > 12 and scblno <= 22 " & _
            "order by LSCPRDLIB_ivscbodyP.scblno"
            
   Set rs = dbHead.OpenRecordset(strSQL)

    If rs.EOF = False Then
        Text36.SetFocus
        Text36.Text = rs.Fields("scbsrk")
    End If
    
    Do While rs.EOF = False
        strTextLine = strTextLine & Trim(rs.Fields("scbtxt")) & vbCrLf
        rs.MoveNext
    Loop
    
    Text11.SetFocus
    Text11.Text = strTextLine
        
   Text9.SetFocus
    
    Case "i"        'insert mode
         
        Text36.SetFocus
        Text36.Text = ""
   
        Text11.SetFocus
        Text11.Text = ""
        
        Text9.SetFocus
        Text9.Text = ""
         
   Text9.SetFocus
       
  End Select
  
End Sub

Private Sub DetermineMode(strSelectSku As String)

'check for the existance of the sku in the header table

Dim rs As Recordset

strSQL = "select count(*) as [reccount] from LSCPRDLIB_IVSCBODYP where " & _
         "LSCPRDLIB_IVSCBODYP.[SCBSKU]='" & strSelectSku & "'"
Set rs = dbHead.OpenRecordset(strSQL)

If rs.Fields("reccount") = 0 Then
    strMode = "i"
Else
    strMode = "u"
End If

End Sub

Private Function CheckLineMode(strSKU As String, intLineNo As Integer) As Boolean
'check if a line exists or not

Dim rs As Recordset

strSQL = "select Count(*) as [reccount] from LSCPRDLIB_ivscbodyp where " & _
        "[scbsku]='" & strSKU & "' " & _
        "and scblno=" & intLineNo
        
Set rs = dbHead.OpenRecordset(strSQL)

If rs.Fields("reccount") > 0 Then
    CheckLineMode = True
Else
    CheckLineMode = False
End If
                
End Function
Private Sub UpdateBlank(strSKU As String, intLineNo As Integer)
'clear any blank lines

Dim strSCBTxt As String
Dim strSCBSRK As String

strSCBTxt = ""
strSCBSRK = ""

strSQL = "update lscprdlib_ivscbodyp set " & _
        "[scbtxt] =""" & strSCBTxt & """," & _
        "[scbsrk] =""" & strSCBSRK & """ " & _
        "where [scbsku]='" & strSKU & "' " & _
        "and scblno =" & intLineNo
        
dbHead.Execute (strSQL)
               
End Sub

