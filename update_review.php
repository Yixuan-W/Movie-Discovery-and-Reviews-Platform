<?php
    $host = "303.itpwebdev.com";
    $user = "ywang843_film_user";
    $pass = "uscitp2024";
    $db = "ywang843_film_reviews";

    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: " . $mysqli->connect_error;
        exit();
    }

    $update_success = false;
    $message = '';

    function isCapitalized($str) {
        return preg_match("/^[A-Z0-9][\w'&:]*$|^([A-Z0-9][\w'&:]*)([\s-][A-Z0-9][\w'&:]*)*$/", $str);
    }

    function isFullSentence($str) {
        return preg_match('/^[A-Z].*[.!?]$/', $str);
    }

    if (
        isset($_POST['review_id'], $_POST['film-name'], $_POST['director-id'], $_POST['review-date'], $_POST['rating'], $_POST['review-input']) &&
        !empty($_POST['review_id']) && !empty($_POST['film-name']) && !empty($_POST['director-id']) && !empty($_POST['review-date']) && !empty($_POST['rating']) && !empty($_POST['review-input'])
    ) {
        $review_id = $_POST['review_id'];
        $film_name = $_POST['film-name'];
        $director_id = $_POST['director-id'];
        $review_date = $_POST['review-date'];
        $rating_id = $_POST['rating'];
        $review_text = $_POST['review-input'];

        if (!isCapitalized($film_name)) {
            $message = "Error: Film name must start with a capital letter.";
        } else if (!isFullSentence($review_text)) {
            $message = "Error: Review must be a full sentence ending with a period, exclamation mark, or question mark.";
        } else {
            $sql = "UPDATE tracks SET title = ?, director_id = ?, date = ?, review = ?, rating_id = ? WHERE track_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sisssi", $film_name, $director_id, $review_date, $review_text, $rating_id, $review_id);

            if ($stmt->execute()) {
                $update_success = true;
                $message = "Review updated successfully.";
            } else {
                $message = "Error updating record: " . $mysqli->error;
            }
            $stmt->close();
        }
    } else {
        $message = "Required fields are missing.";
    }

    $mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Review Confirmation</title>
    <link rel="stylesheet" href="feature.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Thyssen', cursive;
            font-size: 1.3em;
            padding: 10px;
            background-color: #2C2C2C;
        }
        .alert {
            margin-top: 20px;
        }
        .return-link {
            margin-top: 20px;
        }
        .return-link a {
            display: inline-block;
            padding: 10px;
            color: #fff;
            font-size: 1em;
            background-color: #007bff;
            border: 1px solid #007bff;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .return-link a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($update_success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="return-link">
            <a href="secondpage.php" class="btn btn-primary">Return Back</a>
        </div>
    </div>
</body>
</html>
       
