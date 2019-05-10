// Authenticated user's login token, to be passed to API calls
var authToken = "";

// Attempt a login using the credentials entered in the login form
// Upon a successful login, automatically calls the handler for populating buttons
function sendLoginCredentials() {
    // Get data
    var formData = {};
    formData.username = $("input#username").val();
    formData.password = $("input#password").val();

    // Send it via AJAX POST
    $.ajax({
        url: "rest.php/v1/user",
        type: "POST",
        dataType: "json",
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.status == "FAIL") {
                $("div#error").css("display", "block");
                $("div#error").html("<p>" + response.msg + "</p>");
            } else {
                authToken = response.token;
                $("div#error").css("display", "none");
                $("div#auth-content").css("display", "block");
                $("form#login-form").css("display", "none");
                $("#login-banner").css("display", "none");
                prepareDataPage();  // Prepare the post-login screen (add buttons, first-time table population)
            }
        },
        error: function() {
            alert("Error in posting login data");
        }
    });
}

// Prepares the post-login screen with dynamic button population and table entries
function prepareDataPage() {
    populateButtons();
    getSummaryOfItems();
    getRecentItems();
}

// Add a new database entry, get the summary, and update the table
// Receives itemPK (itemKey) from the onclick handler set by dynamic button creation
function addItemAndUpdate(itemKey) {
    addDatabaseEntry(itemKey);
    getSummaryOfItems();
    getRecentItems();
}

// Add a new consumed item based on authenticated user to the diary database
function addDatabaseEntry(itemKey) {

    var updateData = {};
    updateData.token = authToken;
    updateData.itemPK = itemKey;

    // Send the new consumed item as an update query to database
    $.ajax({
        url: "rest.php/v1/items",
        type: "POST",
        dataType: "json",
        data: JSON.stringify(updateData),
        success: function(response) {
            if (response.status == "FAIL") {

            } else {
                
            }
        },
        error: function(xhr, status, error) {
            var err = eval("(" + xhr.responseText + ")");
        }
    });
}

// Request data from DB to dynamically populate the buttons area
function populateButtons() {
    $.ajax({
        url: "rest.php/v1/items",
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.status == "FAIL") {

            } else {
                $.each(response.items, function(i, item) {
                    $("div#buttons").append("<button class=\"btn btn-default\" onclick=\"addItemAndUpdate(this.id)\" id=\"" + item.pk + "\">" + item.item + "</button>");
                });
            }
        },
        error: function() {
            alert("Error in getting diary items for buttons");
        }
    });
}

// Request a list of the 30 most recently consumed items for a particular user and display in a table
function getRecentItems() {
    $.ajax({
        url: "rest.php/v1/items/" + authToken,
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.status == "FAIL") {
                $("div#error").css("display", "block");
                console.log("Request failed: " + response.msg);
            } else {
                $("#update-log-body").html(prepareUpdateLogTable(response.items));
                $("div#error").css("display", "none");
                $("form#login-form").css("display", "none");
            }
        },
        error: function(xhr, status, error) {
            alert("Error getting summary of items");
            var err = eval("(" + xhr.responseText + ")");
            console.log(err);
        }
    });
}

// Properly format the table data for the update log body
function prepareUpdateLogTable(data) {
    var tableMarkup = "";

    // Create a row for each item and its timestamp
    $.each(data, function(i, item) {
        tableMarkup += `
        <tr>
            <td>${item.item}</td>
            <td>${item.timestamp}</td>
        </tr>
        `;
    });

    return tableMarkup;
}

function prepareSummaryOfItemsTable(data){
	var tableContent = "";

	$.each(data, function(i, item) {
		tableContent += `
        	<tr>
        		<td>${item.item}</td>
        		<td>${item.itemCount}</td>
        	</tr>
        `;
    });
	return tableContent;
}

// Request the updated data from the diary database
function getSummaryOfItems() {
    // GET the items
    $.ajax({
        url: "rest.php/v1/itemsSummary/" + authToken,
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.status == "FAIL") {
                $("div#error").css("display", "block");
                console.log("Request failed: " + response.msg);
            } else {
                $.each(response.itemCount, function(i, item) {
                    $("#diary-items-body").html(prepareSummaryOfItemsTable(response.itemCount));
                });

                $("div#error").css("display", "none");
                $("form#login-form").css("display", "none");
            }
        },
        error: function(xhr, status, error) {
            alert("Error getting summary of items");
            var err = eval("(" + xhr.responseText + ")");
            console.log(err);
        }
    });
}
