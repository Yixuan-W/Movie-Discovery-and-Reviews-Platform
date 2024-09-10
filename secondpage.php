<?php
	$host = "303.itpwebdev.com";
	$user = "ywang843_film_user";
	$pass = "uscitp2024";
	$db = "ywang843_film_reviews";

	$mysqli = new mysqli($host, $user, $pass, $db);
	if ($mysqli->connect_errno) {
    echo "Connection Error: " . $mysqli->connect_error;
    exit();
	}

	$mysqli->set_charset('utf8');

	$ratingsSql = "SELECT * FROM ratings;";
	$ratingsResult = $mysqli->query($ratingsSql);
	if ($ratingsResult === false) {
    echo "Error: " . $mysqli->error;
    exit();
	}

	$directorsSql = "SELECT * FROM directors;";
	$directorsResult = $mysqli->query($directorsSql);

	function getDirectorId($directorName, $mysqli) {
    $query = "SELECT director_id FROM directors WHERE director = '$directorName';";
    $result = $mysqli->query($query);
    return $result ? $result->fetch_assoc()['director_id'] : false;
	}

	function getRatingId($ratingName, $mysqli) {
    $query = "SELECT rating_id FROM ratings WHERE rating = '$ratingName';";
    $result = $mysqli->query($query);
    return $result ? $result->fetch_assoc()['rating_id'] : false;
	}

	function isCapitalized($str) {
    return preg_match('/^[A-Z][a-z]*([\s-][A-Z][a-z]*)*$/', $str);
	}

	function addNewDirector($directorName, $mysqli) {
    if (!isCapitalized($directorName)) {
        return false;
    }
    $query = "INSERT INTO directors (director) VALUES (?);";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("s", $directorName);
        $stmt->execute();
        if ($stmt->error) {
          echo "SQL Error: " . $stmt->error;
          $stmt->close();
          return false;
        } else {
          $newId = $stmt->insert_id;
          $stmt->close();
          return $newId;
        }
    }
    return false;
	}

	function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_review'])) {
      $errors = [];

      $filmName = sanitizeInput($_POST['film-name'] ?? '');
      $directorName = sanitizeInput($_POST['new-director-name'] ?? '');
      $reviewDate = sanitizeInput($_POST['review-date'] ?? '');
      $ratingId = sanitizeInput($_POST['rating'] ?? '');
      $reviewText = sanitizeInput($_POST['review-input'] ?? '');

      if (!preg_match("/^[A-Z0-9][\w'&:]*$|^([A-Z0-9][\w'&:]*)([\s-][A-Z0-9][\w'&:]*)*$/", $filmName)) {
        $errors['filmName'] = "Each word in the film name must start with a capital letter.";
      }

      if ($directorName && !preg_match('/^([A-Z][a-z]*([\s-][A-Z][a-z]*)*)+$/', $directorName)) {
        $errors['directorName'] = "Director name must start with a capital letter.";
      }

      if (!preg_match('/^[A-Z].*[.!?]$/', $reviewText)) {
        $errors['reviewText'] = "Review must be a full sentence ending with a period, exclamation mark, or question mark.";
      }

      if (!empty($errors)) {
        foreach ($errors as $field => $message) {
          echo "Error in {$field}: {$message}<br>";
        }
        exit();
      }

      $directorId = null;
	    if (!empty($directorName)) {
        $directorId = addNewDirector($directorName, $mysqli);
        if ($directorId === false) {
          echo "Error: Could not add new director.";
          exit();
        }
	    } else if (!empty($_POST['director-id'])) {
	      $directorId = $_POST['director-id'];
	    }

	    if ($directorId) {
	      $stmt = $mysqli->prepare("INSERT INTO tracks (title, director_id, date, review, rating_id) VALUES (?, ?, ?, ?, ?)");
	      $stmt->bind_param("sisss", $filmName, $directorId, $reviewDate, $reviewText, $ratingId);
	      if (!$stmt->execute()) {
	        echo "SQL Error: " . $stmt->error;
	      } else {
	        echo "Review added successfully!";
	      }
	      $stmt->close();
	    }

		  header("Location: secondpage.php");
		  exit();

    } else if (isset($_POST['delete_review'])) {
      $deleteId = sanitizeInput($_POST['delete_id']);
      $deleteSql = "DELETE FROM tracks WHERE track_id = ?";

      if ($stmt = $mysqli->prepare($deleteSql)) {
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        if ($stmt->error) {
          echo "SQL Error: " . $stmt->error;
        } else {
          echo "Review deleted successfully!";
        }
        $stmt->close();
      }

      header("Location: secondpage.php");
      exit();
    }
	}

	$reviewsPerPage = 5;
  $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  $offset = ($currentPage - 1) * $reviewsPerPage;

  $totalReviewsQuery = "SELECT COUNT(*) AS total FROM tracks";
  $totalResult = $mysqli->query($totalReviewsQuery);
  $totalRow = $totalResult->fetch_assoc();
  $totalReviews = $totalRow['total'];
  $totalPages = ceil($totalReviews / $reviewsPerPage);

	$reviewsSql = "SELECT tracks.track_id, tracks.title, directors.director, tracks.date, tracks.review, ratings.rating 
                 FROM tracks 
                 LEFT JOIN directors ON tracks.director_id = directors.director_id
                 LEFT JOIN ratings ON tracks.rating_id = ratings.rating_id
                 ORDER BY tracks.date DESC
                 LIMIT $offset, $reviewsPerPage";
  
  $reviewsResult = $mysqli->query($reviewsSql);

	$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Join the Cinema Spectrum community and share your unique movie reviews and ratings. Engage with other film lovers, discuss your favorite directors, and explore diverse cinematic perspectives.">
  	
	<title>Final Project | Second Page</title>

	<link rel="stylesheet" href="feature.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">

	<style>
		#header {
		  background-image: url('img/noface.jpg');
		  text-align: center;
		}
		.header-paragraph {
			position: absolute;
			bottom: 0;
			width: 100%;
			padding: 20px;
		  color: black;
		  text-shadow: none;
		}
		#page-wrapper {
      width: 100%;
    }
    body {
    	background-color: #2C2C2C;
    }
		h1 {
			color: black;
			margin: 0px;
			font-size: 5em;
			font-family: ConfettiStream, fantasy;
		}
		h3 {
			color: black;
			margin: 30px;
			font-size: 1.5em;
			font-family: ConfettiStream, fantasy;
		}
		h2 {
			font-size: 2.5em;
			color: #F1F1F1;
			font-family: CalligraphyFLF, cursive;
		}
		label, .add-a {
      color: #F1F1F1;
      font-family: 'Thyssen', cursive;
      font-size: 1.3em;
    }
    .delete-a, .edit-a {
      font-family: 'Thyssen', cursive;
    }
	  .movie-content p, .movie-content h2 {
	  	color: black;
	  	font-size: 1.1em;
	  }
	  .movie-content > h2 {
	  	color: darkslategray;
	  	font-size: 2.5em;
	  }
	  .error-message {
		  font-size: 0.8em;
		}
		.movie-content {
		    padding-bottom: 20px;
		}
		.button-container {
		    display: flex;
		    align-items: center;
		    gap: 10px;
		    bottom: 10px;
		    right: 10px;
		}
		.page-link {
			font-family: 'Thyssen', cursive;
		}
	</style>

</head>

<body>
	<div id="header" class="container-fluid">
    <div class="row h-100">
      <div class="col d-flex flex-column justify-content-center">
        <h1>Community Reel</h1>
        <h3>Voices of the Cinema World</h3>
      </div>
    </div>
    <div class="row">
      <div class="col-12 d-flex justify-content-center">
        <p class="header-paragraph col-10 m-0">
          Welcome to the Community Reel, where every opinion shines like a star in the night sky. This is your space to connect, share, and explore the vast universe of cinema through the eyes of fellow movie lovers. From heartwarming reviews that capture the essence of storytelling to spirited discussions that dive deep into cinematic artistry, immerse yourself in a community that celebrates the power of film. Discover hidden gems, debate over blockbusters, and let your voice be heard in the grand mosaic of cinematic opinions. Join the conversation, and let's script our journey through the world of movies together!
        </p>
      </div>
    </div>
	</div>

  <ul id="nav" class="container-fluid">
		<li><a href="firstpage.html">Discover & Watch</a></li>
		<li><a id="active-menu" href="secondpage.php">Community Reviews</a></li>
		<li><a href="thirdpage.html">Curator's Corner</a></li>
	</ul>

	<div class="container-fluid my-3">
    <div class="row justify-content-center">
      <div class="col-8">
        <h2 class="text-center">Add Your Review</h2>
        <form id="review-form" method="POST">
          <div class="form-row">
              <div class="col">
                <label for="film-name">Film Name</label>
                <input type="text" class="form-control" id="film-name" name="film-name" required>
                <span id="film-name-error" class="error-message text-danger"></span>
              </div>
              <div class="col">
                <label for="director-id">Director</label>
                <select name="director-id" id="director-id" class="form-control" required>
                  <option value="" selected>Select One</option>
                  <?php while($director = $directorsResult->fetch_assoc()): ?>
                    <option value="<?php echo $director['director_id']; ?>">
                      <?php echo htmlspecialchars($director['director']); ?>
                    </option>
                  <?php endwhile; ?>
                  <option value="new">Add New Director</option>
                </select>
               <input type="text" name="new-director-name" id="new-director-input" class="form-control mt-2" placeholder="Enter new director name" style="display: none;">
               <span id="new-director-name-error" class="error-message text-danger"></span>
              </div>
              <div class="col">
                <label for="review-date">Date of Watching</label>
                <input type="date" class="form-control" id="review-date" name="review-date" required>
              </div>
              <div class="col">
                <label for="rating">Rating</label>
                <select class="form-control" id="rating" name="rating" required>
                	<option value="" selected>Choose...</option>
                  <?php while($rating = $ratingsResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($rating['rating_id']); ?>">
                      <?php echo htmlspecialchars($rating['rating']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
          </div>
          <div class="form-row mt-3">
            <div class="col">
              <label for="review-input">Your Review</label>
              <textarea class="form-control" id="review-input" name="review-input" rows="3" required></textarea>
              <span id="review-input-error" class="error-message text-danger"></span>
            </div>
          </div>
          <div class="form-row justify-content-center mt-3">
            <button type="submit" name="add_review" class="btn btn-primary add-a w-25">Add</button>
          </div>
        </form>
      </div>
    </div>
  </div>

	<div class="container-fluid">
	  <div class="row justify-content-center">
	    <div class="col-8 mb-3">
	      <ul id="movielist" class="list-group">
	        <?php while ($review = $reviewsResult->fetch_assoc()): ?>
					  <li class="list-group-item d-flex align-items-center position-relative">
					    <div class="d-flex flex-column flex-grow-1 justify-content-center movie-content">
					      <h2><?php echo htmlspecialchars($review['title']); ?></h2>
					      <p class="movie-director">Director: <?php echo htmlspecialchars($review['director']); ?></p>
					      <p class="movie-date">Watched on: <?php echo htmlspecialchars($review['date']); ?></p>
					      <p><?php echo htmlspecialchars($review['review']); ?></p>
					    </div>
					    <div class="rating-stars text-warning position-absolute" style="top: 10px; right: 10px;">
					      <?php
					        $starRating = ['Excellent' => '★★★★★', 'Great' => '★★★★☆', 'Good' => '★★★☆☆', 'Fair' => '★★☆☆☆', 'Poor' => '★☆☆☆☆'];
					        echo $starRating[$review['rating']] ?? '☆☆☆☆☆';
					      ?>
					    </div>
					     <div class="button-container position-absolute bottom-right">
	            <a href="edit_review.php?review_id=<?php echo $review['track_id']; ?>" class="btn btn-warning edit-a">Edit</a>
	            <form method="POST" class="delete-form">
	                <input type="hidden" name="delete_id" value="<?php echo $review['track_id']; ?>">
	                <button type="submit" name="delete_review" class="btn btn-danger delete-a">Delete</button>
	            </form>
	        </div>
					  </li>
					<?php endwhile; ?>
	      </ul>
	    </div> 
	  </div>
	</div>

	<div class="row justify-content-center">
    <div class="col-8">
      <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-center">
          <?php if ($currentPage > 1): ?>
          	<li class="page-item"><a class="page-link" href="?page=1">First</a></li>
          	<li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">Previous</a></li>
          <?php endif; ?>

          <?php
          	$start = max($currentPage - 2, 1);
          	$end = min($currentPage + 2, $totalPages);
          	for ($i = $start; $i <= $end; $i++):
              echo '<li class="page-item '.($i == $currentPage ? 'active' : '').'">';
              echo '<a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
          	endfor;
          ?>

          <?php if ($currentPage < $totalPages): ?>
          	<li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">Next</a></li>
          	<li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>">Last</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
	</div>

  <div id="footer" class="container-fluid">
    <div class="row">
      <div class="col-12 text-center">
        ITP 303 - Final Project of Yixuan Wang &copy; 2024
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
		  const form = document.getElementById('review-form');
		  const filmNameInput = document.getElementById('film-name');
		  const newDirectorInput = document.getElementById('new-director-input');
		  const reviewInput = document.getElementById('review-input');

		  form.addEventListener('submit', function(event) {
		    let isValid = true;

		    const capitalizedPattern = /^[A-Z0-9][\w'&:]*$|^([A-Z0-9][\w'&:]*)([\s-][A-Z0-9][\w'&:]*)*$/;

		    if (!capitalizedPattern.test(filmNameInput.value.trim())) {
		      document.getElementById('film-name-error').textContent = 'Each word in the film name must start with a capital letter.';
		      isValid = false;
		    } else {
		      document.getElementById('film-name-error').textContent = '';
		    }

		    if (newDirectorInput.value && !capitalizedPattern.test(newDirectorInput.value.trim())) {
		      document.getElementById('new-director-name-error').textContent = 'Director name must start with a capital letter.';
		      isValid = false;
		    } else {
		      document.getElementById('new-director-name-error').textContent = '';
		    }

		    const sentencePattern = /^[A-Z].*[.!?]$/;

		    if (!sentencePattern.test(reviewInput.value.trim())) {
		      document.getElementById('review-input-error').textContent = 'Review must be a full sentence ending with a period, exclamation mark, or question mark.';
		      isValid = false;
		    } else {
		      document.getElementById('review-input-error').textContent = '';
		    }

		    if (!isValid) {
		      event.preventDefault();
		    }
		  });

		  const directorSelect = document.getElementById('director-id');
		  directorSelect.addEventListener('change', function() {
		    const addNewDirectorInput = document.getElementById('new-director-input');
		    if (this.value === 'new') {
		      addNewDirectorInput.style.display = 'block';
		      addNewDirectorInput.required = true;
		    } else {
		      addNewDirectorInput.style.display = 'none';
		      addNewDirectorInput.required = false;
		      addNewDirectorInput.value = '';
		    }
		  });
		});
  </script>

</body>
</html>