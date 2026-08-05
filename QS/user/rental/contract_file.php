<?php 
include('../../config/config.php');

// Handle file upload
if (isset($_POST['submit'])) {
    $file = $_FILES['file'];
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileContent = file_get_contents($fileTmpName);
    $mimeType = mime_content_type($fileTmpName);

    $sql = $pdo->prepare("INSERT INTO test (filename, file, mime_type) VALUES (:filename, :file, :mime_type)");
    $sql->execute([
        ':filename' => $fileName,
        ':file' => $fileContent,
        ':mime_type' => $mimeType
    ]);

    echo "File uploaded successfully.";
}

// Serve the file data for modal or download
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = $pdo->prepare("SELECT filename, file, mime_type FROM test WHERE id = :id");
    $sql->execute([':id' => $id]);
    $file = $sql->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        $mimeType = $file['mime_type'];
        $filename = $file['filename'];

        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'])) {
            $fileContent = base64_encode($file['file']);
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
            header('Content-Length: ' . strlen($file['file']));
            echo $file['file']; // Ensure the correct file content is sent
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
    <title>ML RentalFile Upload and View</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="file">Choose file</label>
                <input type="file" class="form-control-file" id="file" name="file">
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Upload</button>
        </form>

        <h2 class="mt-5">View or Download Files</h2>
        <ul class="list-group">
            <?php
            $sql = $pdo->query("SELECT id, filename FROM test");
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
                echo "<li class='list-group-item'>
                        <a href='#' class='view-file' data-id='{$row['id']}'>{$row['filename']}</a>
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
                            embedCode = `<embed src="${fileSrc}" width="100%" height="100%" type="application/pdf" />`;
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
