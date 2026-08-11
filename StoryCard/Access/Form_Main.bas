Attribute VB_Name = "Form_Main"
Attribute VB_Base = "0{B70A362E-BCE8-11D2-8A92-00105AA350F4}"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Attribute VB_TemplateDerived = False
Attribute VB_Customizable = False
Option Compare Database
Option Explicit

Private Sub Command12_Click()

Select Case Frame1.Value

    Case 1
        DoCmd.OpenForm "HeadMaintenance"
    
    Case 2
        DoCmd.OpenForm "BodyMaintenance"
        
    Case 3
        DoCmd.OpenForm "FootMaintenance"
        
 End Select

End Sub

Private Sub Command10_Click()
  
End Sub
