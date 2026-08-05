<?php
ob_start();
session_start();
include '../../config/config.php';

if (ob_get_length()) ob_clean();

if (isset($_GET['id']) && isset($_GET['file'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $requested_col = mysqli_real_escape_string($conn, $_GET['file']);

    // Map the Filename column to the Blob and MimeType columns
    $columnMap = [
        'contractFilename'   => ['blob' => 'contract_file',   'mime' => 'mimeType'],
        'contractFilename2'  => ['blob' => 'contract_file2',  'mime' => 'mimeType2'],
        'contractFilename3'  => ['blob' => 'contract_file3',  'mime' => 'mimeType3'],
        'contractFilename4'  => ['blob' => 'contract_file4',  'mime' => 'mimeType4'],
        'contractFilename5'  => ['blob' => 'contract_file5',  'mime' => 'mimeType5'],
        'contractFilename16' => ['blob' => 'contract_file16', 'mime' => 'mimeType16'],
        'attachment_6_filename'  => ['blob' => 'attachment_6',  'mime' => 'mimeType6'],
        'attachment_7_filename'  => ['blob' => 'attachment_7',  'mime' => 'mimeType7'],
        'attachment_8_filename'  => ['blob' => 'attachment_8',  'mime' => 'mimeType8'],
        'attachment_9_filename'  => ['blob' => 'attachment_9',  'mime' => 'mimeType9'],
        'attachment_10_filename' => ['blob' => 'attachment_10', 'mime' => 'mimeType10'],
        'attachment_11_filename' => ['blob' => 'attachment_11', 'mime' => 'mimeType11'],
        'attachment_12_filename' => ['blob' => 'attachment_12', 'mime' => 'mimeType12'],
        'attachment_13_filename' => ['blob' => 'attachment_13', 'mime' => 'mimeType13'],
        'attachment_14_filename' => ['blob' => 'attachment_14', 'mime' => 'mimeType14'],
        'attachment_15_filename' => ['blob' => 'attachment_15', 'mime' => 'mimeType15']
    ];

    if (array_key_exists($requested_col, $columnMap)) {
        $blobCol = $columnMap[$requested_col]['blob'];
        $mimeCol = $columnMap[$requested_col]['mime'];
        
        // Fetch the binary data and the mime type
        $query = "SELECT $blobCol, $mimeCol, $requested_col FROM create_contract WHERE id = '$id'";
        $result = mysqli_query($conn, $query);

        if ($row = mysqli_fetch_assoc($result)) {
            $pdfData = $row[$blobCol];
            $mimeType = !empty($row[$mimeCol]) ? $row[$mimeCol] : 'application/pdf';
            $fileName = !empty($row[$requested_col]) ? $row[$requested_col] : 'document.pdf';

            if (!empty($pdfData)) {
                // Set headers for binary BLOB output
                header("Content-Type: $mimeType");
                header("Content-Disposition: inline; filename=\"$fileName\"");
                header("Content-Length: " . strlen($pdfData));
                
                echo $pdfData;
                exit;
            } else {
                echo "Error: The database column is empty (No binary data found).";
            }
        } else {
            echo "Error: Record not found.";
        }
    } else {
        echo "Error: Invalid column request.";
    }
}