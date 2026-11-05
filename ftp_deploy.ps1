# FTP Deploy Script for methofresh.com
# Uploads files changed in git to the live server via FTP

$ftpHost   = "ftp.gb.stackcp.com"
$ftpUser   = "mfinnovation.xyz"
$ftpPass   = "92935345781b"
$ftpRoot   = "/methofresh.com"        # Remote base path
$localRoot = "e:\wamp64\www\methofresh.com"

# Files to upload
$files = @('app/Views/partials/wsit_header.php')
if (-not $files) {
    Write-Host "No changed files found"
    exit 0
}

Write-Host "Files to upload:"
$files | ForEach-Object { Write-Host "  $_" }
Write-Host ""

function FTP-Upload {
    param(
        [string]$localFile,
        [string]$remoteFile
    )

    $uri = "ftp://$ftpHost$remoteFile"
    $cred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

    # Ensure remote directory exists by uploading parent dirs first
    $remoteDir = [System.IO.Path]::GetDirectoryName($remoteFile).Replace('\','/')
    
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $req.Credentials = $cred
        $req.UseBinary = $true
        $req.UsePassive = $true
        $req.KeepAlive = $false
        $req.ContentLength = (Get-Item $localFile).Length

        $fileStream = [System.IO.File]::OpenRead($localFile)
        $reqStream = $req.GetRequestStream()
        $fileStream.CopyTo($reqStream)
        $fileStream.Close()
        $reqStream.Close()

        $resp = $req.GetResponse()
        $status = $resp.StatusDescription
        $resp.Close()

        Write-Host "OK: $remoteFile ($status)"
        return $true
    } catch {
        Write-Host "FAILED: $remoteFile - $_"
        return $false
    }
}

$ok = 0
$fail = 0

foreach ($file in $files) {
    $file = $file.Trim()
    if ($file -eq "") { continue }
    
    $localPath = Join-Path $localRoot $file.Replace('/', '\')
    
    # Skip if local file doesn't exist (deleted files)
    if (-not (Test-Path $localPath)) {
        Write-Host "SKIP (deleted): $file"
        continue
    }

    $remotePath = "$ftpRoot/$file"

    $result = FTP-Upload -localFile $localPath -remoteFile $remotePath
    if ($result) { $ok++ } else { $fail++ }
}

Write-Host ""
Write-Host "Done. Uploaded: $ok  Failed: $fail"
