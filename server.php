<?php
	// Team 16 Bookstore (server.php)
	// Authors: Ben Holzhauer (PHP), Nicholas Early (SQL)
	// Class: CS 405G-001
	
        // Allow access to server
        header("Access-Control-Allow-Origin: *");

	// Set up database connection
	$conn = mysqli_connect("mysql.cs.uky.edu", "bgho224", "team16", "bgho224");	
        
	// Start user session if not active
        if (!$_SESSION)
	{
		session_start();
	}

        // Handle post requests
	if ($_POST)
        {
		// Check for user log in
		if (isset($_POST["loginForm"]))
		{
			// Get email and password for log in
			$email = $_POST["email"];
			$password = $_POST["password"];

			// Query database for UID and role of user
			$userResults = mysqli_query($conn, "SELECT uid, role FROM users WHERE email='{$email}' AND password='{$password}'");
                        if ($userResults)
                        {
				// Get session UID and role for being logged in
                                $row = mysqli_fetch_row($userResults);
                                $_SESSION["uid"] = $row[0];
                                $_SESSION["role"] = $row[1];

				// Free query
                                mysqli_free_result($userResults);

                                echo "USER LOGGED IN";
			}
		}

                // Check for user sign up
                if (isset($_POST["signupForm"]))
                {
			// Get data for user sign up

			$email = $_POST["email"];
			$password = $_POST["password"];
			$role = $_POST["role"];
			$fname = $_POST["fname"];
			
			$mname = "'" . $_POST["mname"] . "'";
			if (!$_POST["mname"])
			{
				$mname = "null";
			}

			$lname = $_POST["lname"];
			
			$age = $_POST["age"];
			if (!$age)
			{
				$age = "null";
			}			

			$gender = $_POST["gender"];

			// Query database for user sign up
			$signupResults = mysqli_query($conn, "INSERT INTO users(email, password, role, fname, mname, lname, age, gender) VALUES ('{$email}', '{$password}', '{$role}', '{$fname}', {$mname}, '{$lname}', {$age}, '{$gender}')");
			if ($signupResults)
			{
				// Free query
                                mysqli_free_result($signupResults);

				// Query database for UID and role of user
				$uidResults = mysqli_query($conn, "SELECT MAX(uid) FROM users");
				if ($uidResults)
				{
					// Get session UID and role for being logged in
					$row = mysqli_fetch_row($uidResults);
					$_SESSION["uid"] = $row[0];
                        		$_SESSION["role"] = $role;

					echo "USER SIGNED UP";

					// Free query
					mysqli_free_result($uidResults);
				}
			}
		}

                // Handle making searches
		if (isset($_POST["searchForm"]))
		{
			// Get search term and word
			$term = $_POST["searchTerm"];
			$words = $_POST["searchWords"];
			
			// Check for max price search and query database
			if ($term == "b.price")
			{
				$searchResults = mysqli_query($conn, "SELECT DISTINCT b.isbn, b.name, a.author, b.publisher, b.pubDate, b.subject, b.price, b.quantity FROM books AS b, authors AS a WHERE b.price <= {$words} AND b.isbn=a.isbn");
			}
			if ($term == "k.keyword")
			{
				$searchResults = mysqli_query($conn, "SELECT DISTINCT b.isbn, b.name, a.author, b.publisher, b.pubDate, b.subject, b.price, b.quantity FROM books AS b, authors AS a, book_keywords AS k WHERE {$term} LIKE '%{$words}%' AND b.isbn=a.isbn AND b.isbn=k.isbn");
			}
			// Otherwise, query database for other searches
			else
			{
                        	$searchResults = mysqli_query($conn, "SELECT DISTINCT b.isbn, b.name, a.author, b.publisher, b.pubDate, b.subject, b.price, b.quantity FROM books AS b, authors AS a WHERE {$term} LIKE '%{$words}%' AND b.isbn=a.isbn");
			}

			if ($searchResults)
			{
				// Get list of books
				$books = [];
				while ($row = mysqli_fetch_row($searchResults))
				{
					// Query keywords from database
					$keywordResults = mysqli_query($conn, "SELECT keyword FROM book_keywords WHERE isbn={$row[0]}");
					if ($keywordResults)
					{
						$keywords = [];
						while ($row2 = mysqli_fetch_row($keywordResults))
						{
							if ($row2[0])
							{
								$keywords[] = $row2[0];
							}
						}

						$row[] = $keywords;

						// Free query
						mysqli_free_result($keywordResults);
					}

					$books[] = $row;
				}

				echo json_encode($books);
	
				// Free query
				mysqli_free_result($searchResults);
		
			}
                }

		// Check for list of book request
		if (isset($_POST["getBooks"]))
		{
			// Query database for list of books
			$bookResults = mysqli_query($conn, "SELECT b.name, a.author, b.isbn FROM books AS b, authors AS a WHERE b.isbn=a.isbn");
			if ($bookResults)
			{
				// Get list of books
				$books = [];
				while ($row = mysqli_fetch_row($bookResults))
				{
					$books[] = [$row[0], $row[1], $row[2]];
				}

				echo json_encode($books);

				// Free query
				mysqli_free_result($bookResults);
			}
		}
                
                // Handle making orders
                if (isset($_POST["orderForm"]))
		{
			// Get order form data
                        $uid = $_SESSION["uid"];
			$isbn = $_POST["book"];
			$ccNumber = $_POST["ccNumber"];
			$quantity = $_POST["quantity"];
			$billingAddr = $_POST["billingAddr"];
			$shippingAddr = $_POST["shippingAddr"];

			// Determine cost by querying database for book price
			$priceResults = mysqli_query($conn, "SELECT price FROM books WHERE isbn={$isbn}");
			if ($priceResults)
			{
				$row = mysqli_fetch_row($priceResults);
				$cost = $row[0] * $quantity;
			
				// Free query
				mysqli_free_result($priceResults);

				// Query database for reducing left quantity
				$quantityResults = mysqli_query($conn, "UPDATE books SET quantity = quantity - 1 WHERE isbn={$isbn}");
				if ($quantityResults)
				{
					// Free query
					mysqli_free_result($quantityResults);

					// Query database for order processing
					$orderResults = mysqli_query($conn, "INSERT INTO orders (uid, isbn, ccNumber, cost, status, quantity, billingAddr, orderDate, shippingAddr) VALUES ({$uid}, {$isbn}, {$ccNumber}, {$cost}, 'Pending', {$quantity}, '{$billingAddr}', NOW(), '{$shippingAddr}')");
					if ($orderResults)
					{
						echo "ORDER PROCESSED";

						// Free query
						mysqli_free_result($orderResults);
					}
				}
				else
				{
					echo mysqli_error($conn);
				}
                	}
		}
                
                // Handle writing reviews
                if (isset($_POST["reviewForm"]))
		{
			// Get review form data
                        $uid = $_SESSION["uid"];
			$isbn = $_POST["book"];
			$rating = $_POST["rating"];
			$review = $_POST["review"];

			// Query database for review writing
			$reviewResults = mysqli_query($conn, "INSERT INTO reviews (uid, isbn, rating, review) VALUES ({$uid}, {$isbn}, {$rating}, '{$review}')");
			if ($reviewResults)
			{
				echo "REVIEW WRITTEN";
			
				// Free query
				mysqli_free_result($reviewResults);
			}
                }
                
                // Handle checking order history
                if (isset($_POST["history"]))
		{
			// Get session UID
                        $uid = $_SESSION["uid"];

			// Query database for user order history
                        $historyResults = mysqli_query($conn, "SELECT o.orderDate, o.isbn, b.name, a.author, o.quantity, o.cost, o.shippingAddr, o.status, o.ccNumber, o.billingAddr FROM orders AS o, books AS b, authors AS a WHERE o.uid={$uid} AND o.isbn=b.isbn AND b.isbn=a.isbn");
			if ($historyResults)
                        {
				// Get history for user
                                $history = [];
                                while ($row = mysqli_fetch_row($historyResults))
                                {
                                        $history[] = $row;
                                }

                                echo json_encode($history);

				// Free query
                                mysqli_free_result($historyResults);
                        }
                }
                
                // Handle management book adding
                if (isset($_POST["addBook"]))
                {
			// Get book adding data

                        $isbn = $_POST["isbn"];
                        $name = $_POST["name"];
			$author = $_POST["author"];
			$publisher = $_POST["publisher"];
			$pubDate = $_POST["pubDate"];
			$subject = $_POST["subject"];
			
			$summary = "'" . $_POST["summary"] . "'";
			if (!$_POST["summary"])
                        {
                                $summary = "null";
                        }

			$price = $_POST["price"];
			$quantity = $_POST["quantity"];
		
			// Query database for adding book
                        $addBookResults = mysqli_query($conn, "INSERT INTO books (isbn, name, publisher, pubDate, subject, summary, price, quantity) VALUES ({$isbn}, '{$name}', '{$publisher}', '{$pubDate}', '{$subject}', {$summary}, {$price}, {$quantity})");
                        if ($addBookResults)
                        {
				// Free query
                                mysqli_free_result($addBookResults);

				// Query database for adding corresponding author
				$addAuthorResults = mysqli_query($conn, "INSERT INTO authors (author, isbn) VALUES ('{$author}', {$isbn})");
				if ($addAuthorResults)
				{
					// Free query
					mysqli_free_result($addAuthorResults);

					// Query database for adding default null keyword
					$addKeywordResults = mysqli_query($conn, "INSERT INTO book_keywords (isbn, keyword) VALUES ({$isbn}, null)");
					if ($addKeywordResults)
					{
						echo "BOOK ADDED";
		
						// Free query
						mysqli_free_result($addKeywordResults);
					}
					else
					{
						echo mysqli_error($conn);
					}
				}
				else
				{
					echo mysqli_error($conn);
				}
			}
			else
			{
				echo mysqli_error($conn);
			}
                }

                // Handle management book updating
                if (isset($_POST["updateBook"]))
                {
			// Get book updating data

                	$oldIsbn = $_POST["book"];

			$isbn = $_POST["isbn"];
			if ($isbn)
			{
				$isbn = "isbn=" . $isbn . ", ";
			}

			$name = $_POST["name"];
			if ($name)
			{
				$name = "name='" . $name . "', ";
			}

			$author = $_POST["author"];
			if ($author)
			{
				$author = "author='" . $author . "'";
			}

			$publisher = $_POST["publisher"];
			if ($publisher)
			{
				$publisher = "publisher='" . $publisher . "', ";
			}

                        $pubDate = $_POST["pubDate"];
			if ($pubDate)
			{
				$pubDate = "pubDate=" . $pubDate . ", ";
			}

                        $subject = $_POST["subject"];
			if ($subject)
			{
				$subject = "subject='" . $subject . "', ";
			}

			$summary = $_POST["summary"];
			if ($summary)
			{
				$summary = "summary='" . $summary . "', ";
			}

			$price = $_POST["price"];
			if ($price)
			{
				$price = "price=" . $price . ", ";
			}

			$quantity = $_POST["quantity"];
			if ($quantity)
			{
				$quantity = "quantity=" . $quantity . ", ";
			}

			// Query database for booking updating
			$updateBookResults = mysqli_query($conn, "UPDATE books SET {$isbn}{$name}{$publisher}{$pubDate}{$subject}{$summary}{$price}{$quantity}language=null WHERE isbn={$oldIsbn}");
                	if ($updateBookResults)
			{
				echo "BOOK UPDATED";

				// Free query
				mysqli_free_result($updateBookResults);
			}

			// Check if author is updated
			if ($author)
			{
				// Query database for author updating
				$updateAuthorResults = mysqli_query($conn, "UPDATE authors SET {$isbn}{$author} WHERE isbn={$oldIsbn}");
				if ($updateAuthorResults)
				{
					echo "AUTHOR UPDATED";
	
					// Free query
					mysqli_free_result($updateAuthorResults);
				}
			}
		}

                // Handle management book deletion
                if (isset($_POST["deleteBook"]))
                {
			// Get ISBN of book to delete
                	$isbn = $_POST["book"];

			// Query database for corresponding deletion
			$deleteAuthorResults = mysqli_query($conn, "DELETE FROM authors WHERE isbn={$isbn}");
			if ($deleteAuthorResults)
			{
				// Free query
				mysqli_free_result($deleteAuthorResults);

				// Query database for book deletion
				$deleteBookResults = mysqli_query($conn, "DELETE FROM books WHERE isbn={$isbn}");
                       		if ($deleteBookResults)
                       		{
					echo "BOOK DELETED";
					
					// Free query
					mysqli_free_result($deleteBookResults);
				}
				
			}
                }

                // Handle management keyword adding
                if (isset($_POST["addKeyword"]))
                {
			// Get ISBN and keyword of book
                        $isbn = $_POST["book"];
                        $keyword = $_POST["keyword"];
			
			// Query database for adding keyword
			$addKeywordResults = mysqli_query($conn, "INSERT INTO book_keywords(isbn, keyword) VALUES ({$isbn}, '{$keyword}')");
                        if ($addKeywordResults)
                        {
				echo "KEYWORD ADDED";

				// Free query
                                mysqli_free_result($addKeywordResults);
                        }
                }

                // Handle management keyword updating
                if (isset($_POST["updateKeyword"]))
                {
			// Get ISBN, old keyword, and new keyword
			$isbn = $_POST["book"];
                        $oldKeyword = $_POST["oldKeyword"];
			$newKeyword = $_POST["newKeyword"];

			// Query database for updating keyword
			$updateKeywordResults = mysqli_query($conn, "UPDATE book_keywords SET keyword='{$newKeyword}' WHERE keyword='{$oldKeyword}' AND isbn={$isbn}");
			if ($updateKeywordResults)
			{
				echo "KEYWORD UPDATED";
		
				// Free query
				mysqli_free_result($updateKeywordResults);
			}
                }

                // Handle management keyword deletion
                if (isset($_POST["deleteKeyword"]))
                {
			// Get isbn and keyword to delete
			$isbn = $_POST["book"];
                        $keyword = $_POST["keyword"];

			// Query database for deleting keyword
			$deleteKeywordResults = mysqli_query($conn, "DELETE FROM book_keywords WHERE keyword='{$keyword}' AND isbn={$isbn}");
			if ($deleteKeywordResults)
			{
				echo "KEYWORD DELETED";

				// Free query
				mysqli_free_result($deleteKeywordResults);
			}
                }

                // Check if user logs out
                if (isset($_POST["logout"]))
                {
			echo "USER LOGGED OUT";

                        // End and destroy session
                        session_unset();
                        session_destroy();
                }
        }
	else
	{
		// Get UID and role of current user
		echo json_encode([$_SESSION["uid"], $_SESSION["role"]]);
	}

	// Close database connection
	$conn->close();
?>
