<?php
include('../../config/config.php'); // Ensure this file sets up the database connection

// Serve the file data for modal or download
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize and validate ID

    // Prepare and execute SQL statement
    $stmt = $conn->prepare("SELECT contractFilename, contract_file, mimeType FROM create_contract WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    $stmt->close();

    if ($file) {
        $mimeType = $file['mimeType'];
        $filename = $file['contractFilename'];

        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'])) {
            $fileContent = base64_encode($file['contract_file']);
            echo json_encode([
                'mime_type' => $mimeType,
                'file_content' => $fileContent,
                'file_name' => $filename
            ]);
        } elseif (in_array($mimeType, ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
            header('Content-Description: File Transfer');
            header("Content-Type: $mimeType");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($file['contract_file']));
            echo $file['contract_file'];
            exit;
        } else {
            echo json_encode(['error' => 'Unsupported file type.']);
        }
    } else {
        echo json_encode(['error' => 'File not found.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload and View</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mt-5">View or Download Files</h2>
        <ul class="list-group">
            <?php
            // Fetch files from database
            $result = $conn->query("SELECT id, contractFilename FROM create_contract WHERE id = 1");
            while ($row = $result->fetch_assoc()) {
                echo "<li class='list-group-item'>
                        <a href='#' class='view-file' data-id='{$row['id']}'>{$row['contractFilename']}</a>
                      </li>";
            }
            ?>
        </ul>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="fileModal" tabindex="-1" role="dialog" aria-labelledby="fileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fileModalLabel">View File</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="fileContent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.view-file').click(function(e) {
                e.preventDefault();
                const fileId = $(this).data('id');

                $.get('?id=' + fileId, function(data) {
                    const fileData = JSON.parse(data);

                    if (fileData.mime_type.startsWith('image/') || fileData.mime_type === 'application/pdf') {
                        const fileSrc = `data:${fileData.mime_type};base64,${fileData.file_content}`;
                        let embedCode = '';

                        if (fileData.mime_type.startsWith('image/')) {
                            embedCode = `<img src="${fileSrc}" class="img-fluid" alt="File Image" />`;
                        } else if (fileData.mime_type === 'application/pdf') {
                            embedCode = `<embed src="${fileSrc}" width="100%" height="800px" type="application/pdf" />`;
                        }

                        $('#fileContent').html(embedCode);
                        $('#fileModal').modal('show');
                    } else if (fileData.mime_type === 'text/csv' || fileData.mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                        window.open(`?id=${fileId}`, '_blank');
                    } else {
                        alert('Unsupported file type.');
                    }
                });
            });
        });
    </script>
</body>
</html>
