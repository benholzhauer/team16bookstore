// Team 16 Bookstore (main.js)
// Author: Ben Holzhauer
// Class: CS 405G-001

window.onload = function () {
  // Define user UID and role
  var uid;
  var role;

  // Get session data from server to check if user is logged in
  $.get("server.php", function (data) {
    console.log(data);

    // Store user UID and role
    uid = JSON.parse(data)[0];
    role = JSON.parse(data)[1];

    // Check if UID and role exist
    if (uid && role) {
      // Check if user is manager
      if (role == "manager") {
        // Show manager options in navigation bar and on main page
        $("#manageNav").show();
        $("#manageLink").show();
      } else {
        // Redirect to home if trying to use manager options as customer
        if (window.location.href.indexOf("manage") >= 0) {
          window.location.href = "index.html";
        }
      }

      $("#features").show(); // Show user options in navigation bar
      $("#login").hide(); // Hide log in content from main page
      $("#choices").show(); // Show user options on main page
      $("#loginNav").hide(); // Hide log in link
      $("#signupNav").hide(); // Hide sign up link
      $("#logoutNav").show(); // Show log out link

      // Redirect to home if trying to sign up when already logged in
      if (window.location.href.indexOf("signup") >= 0) {
        window.location.href = "index.html";
      }
    } else {
      // Redirect to home if trying to use options when logged out
      if (
        window.location.href.indexOf("search") >= 0 ||
        window.location.href.indexOf("order") >= 0 ||
        window.location.href.indexOf("review") >= 0 ||
        window.location.href.indexOf("history") >= 0 ||
        window.location.href.indexOf("manage") >= 0
      ) {
        window.location.href = "index.html";
      }
    }
  });

  // Check for log in form submissions
  $("#login form").submit(function () {
    // Send log in data to server
    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: $(this).serialize(),
      success: function (response) {
        console.log(response);

        window.location.href = "index.html";
      },
    });
    return false;
  });

  // Check for sign up form submissions
  $("#signup form").submit(function () {
    // Send sign up data to server
    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: $(this).serialize(),
      success: function (response) {
        console.log(response);

        window.location.href = "index.html";
      },
    });
    return false;
  });

  // Check if search term is changed to date
  $("#search form select").click(function () {
    if ($(this).find("option:selected").val() == "b.pubDate") {
      // Change search word input to date
      $("#search form input[name=searchWords]").attr("type", "date");
    }
  });

  // Check for search form submissions
  $("#search form").submit(function (e) {
    // Show results table
    $("#results").show();

    // Highlight search term in results table
    var searchTerm = $(this).find("option:selected").val();
    $("#results #" + searchTerm).addClass("text-primary");

    // Disable new submissions and changing search term
    $(this).find(":submit").prop("disabled", true);
    $("#search form select").prop("disabled", true);

    // Send search data to server
    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: $(this).serialize() + "&searchTerm=" + searchTerm,
      success: function (response) {
        console.log(response);

        // Get length of search data array and add to results table
        var length = JSON.parse(response).length;
        for (var i = 0; i < length; i++) {
          // Add book info to table
          var row = "<tr>";
          for (var j = 0; j < JSON.parse(response)[i].length; j++) {
            row += "<td>" + JSON.parse(response)[i][j] + "</td>";
          }
          row += "</tr>";
          $("#results tbody").append(row);
        }

        $("#results").trigger("update");
      },
    });
    return false;
  });

  // Sort search results on request
  $("#results").tablesorter(); // Utilize JQuery plugin
  $("#results th").click(function () {
    // Sort table by clicked column
    $("#sort").remove();
    $(this).prepend("<span id='sort'>></span>");
  });

  // Get list of books from server
  if ($("select[name=book]").length) {
    $.ajax({
      type: "POST",
      url: "server.php",
      data: "getBooks",
      success: function (response) {
        console.log(response);

        // Get length of book list and add book options
        var length = JSON.parse(response).length;
        for (var i = 0; i < length; i++) {
          // Get book title and author
          var title = JSON.parse(response)[i][0];
          var author = JSON.parse(response)[i][1];
          var isbn = JSON.parse(response)[i][2];

          // Add book option
          $("select[name=book]").append("<option value=" + isbn + ">" + title + " - " + author + "</option>");
        }
      },
    });
  }

  // Check for order form submissions
  $("#order form").submit(function () {
    // Show success message and disable new submissions
    $("#success").show();
    $(this).find(":submit").prop("disabled", true);

    // Send order data to server
    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: $(this).serialize(),
      success: function (response) {
        console.log(response);
      },
    });
    return false;
  });

  // Check for review form submissions
  $("#review form").submit(function () {
    // Show success message and disable new submissions
    $("#success").show();
    $(this).find(":submit").prop("disabled", true);

    // Send review data to server
    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: $(this).serialize(),
      success: function (response) {
        console.log(response);
      },
    });
    return false;
  });

  // Check if user checks order history
  if (window.location.href.indexOf("history") >= 0) {
    // Request history from server
    $.ajax({
      type: "POST",
      url: "server.php",
      data: "history",
      success: function (response) {
        console.log(response);

        // Get length of history data array and add to history table
        var length = JSON.parse(response).length;
        for (var i = 0; i < length; i++) {
          // Add order info to table
          var row = "<tr>";
          for (var j = 0; j < JSON.parse(response)[i].length; j++) {
            row += "<td>" + JSON.parse(response)[i][j] + "</td>";
          }
          row += "</tr>";
          $("#historyTable tbody").append(row);
        }

        $("#historyTable").trigger("update");

      },
    });
  }

  // Sort order history on request
  $("#historyTable").tablesorter(); // Utilize JQuery plugin
  $("#historyTable th").click(function () {
    // Sort table by clicked column
    $("#sort").remove();
    $(this).prepend("<span id='sort'>></span>");
  });


  // Check if manage task is selected
  $("#manage select[name=task]").click(function () {
    // Show corresponding form and hide all others
    var form = "#" + $(this).find("option:selected").val();
    $(form).show();
    $("#manage form").not(form).hide();
  });

  // Check for manage form submissions
  $("#manage form").submit(function () {
    // Show success message, disable new submissions, and disable changing task
    $("#success").show();
    $(this).find(":submit").prop("disabled", true);
    $("#manage select[name=task]").prop("disabled", true);

    // Send task data to server
    $.ajax({
      type: "POST",
      url: $(this).attr("action"),
      data: $(this).serialize(),
      success: function (response) {
        console.log(response);
      },
    });
    return false;
  });

  // Check if user logs out
  $("#logoutNav a").click(function () {
    $.ajax({
      type: "POST",
      url: "server.php",
      data: "logout",
      success: function (response) {
        console.log(response);

        window.location.href = "index.html";
      },
    });
    return false;
  });
};
