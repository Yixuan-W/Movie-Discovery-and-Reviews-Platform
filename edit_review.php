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

    if (!isset($_GET['review_id']) || empty($_GET['review_id'])) {
        echo "Invalid Review ID";
        exit();
    }

    $review_id = $_GET['review_id'];

    $sql = "SELECT * FROM tracks WHERE track_id = ?;";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo "No records found.";
        $mysqli->close();
        exit();
    }

    $directorsSql = "SELECT * FROM directors;";
    $directorsResult = $mysqli->query($directorsSql);

    $ratingsSql = "SELECT * FROM ratings;";
    $ratingsResult = $mysqli->query($ratingsSql);

    $review = $result->fetch_assoc();

    $mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Edit Review</title>

    <link rel="stylesheet" href="feature.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        .container {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            margin-top: 5px;
        }
        .form-label {
            font-weight: bold;
            font-family: 'Thyssen', cursive;
            font-size: 1.3em;
            color: #F1F1F1;
        }
        .btn-primary {
            margin-top: 10px;
        }
        .update-a {
            color: #F1F1F1;
            font-family: 'Thyssen', cursive;
            font-size: 1.3em;
        }
        h1 {
            font-size: 2.5em;
            font-family: 'Thyssen', cursive;
            color: #F1F1F1;
        }
        body {
            background-color: #2C2C2C;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Review</h1>
        <form id="edit-review-form" action="update_review.php" method="post">
            <input type="hidden" name="review_id" value="<?php echo $review['track_id']; ?>">

            <div class="form-group">
                <label for="film-name" class="form-label">Film Name:</label>
                <input type="text" class="form-control" id="film-name" name="film-name" value="<?php echo htmlspecialchars($review['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="director-id" class="form-label">Director:</label>
                <select name="director-id" id="director-id" class="form-control" required>
                    <option value="" disabled>Select One</option>
                    <?php while($director = $directorsResult->fetch_assoc()): ?>
                        <option value="<?php echo $director['director_id']; ?>" <?php echo ($director['director_id'] == $review['director_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($director['director']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="review-date" class="form-label">Date of Watching:</label>
                <input type="date" class="form-control" id="review-date" name="review-date" value="<?php echo htmlspecialchars($review['date']); ?>" required>
            </div>

            <div class="form-group">
                <label for="rating" class="form-label">Rating:</label>
                <select class="form-control" id="rating" name="rating" required>
                    <option value="" disabled>Select One</option>
                    <?php while($rating = $ratingsResult->fetch_assoc()): ?>
                        <option value="<?php echo $rating['rating_id']; ?>" <?php echo ($rating['rating_id'] == $review['rating_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rating['rating']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="review-input" class="form-label">Your Review:</label>
                <textarea class="form-control" id="review-input" name="review-input" rows="5" required><?php echo htmlspecialchars($review['review']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary update-a">Update Review</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editForm = document.getElementById('edit-review-form');
            editForm.addEventListener('submit', function(event) {
                let isValid = true;

                const filmNameInput = document.getElementById('film-name');
                if (!/^[A-Z0-9][\w'&:]*$|^([A-Z0-9][\w'&:]*)([\s-][A-Z0-9][\w'&:]*)*$/.test(filmNameInput.value.trim())) {
                    alert('Each word in the film name must start with a capital letter.');
                    isValid = false;
                }

                const reviewInput = document.getElementById('review-input');
                if (!/^[A-Z].*[.!?]$/.test(reviewInput.value.trim())) {
                    alert('Review must be a full sentence ending with a period, exclamation mark, or question mark.');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>

</body>
</html>
