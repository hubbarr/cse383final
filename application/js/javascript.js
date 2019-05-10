var athTok = "";

function logn() {
    var formData = {};
    formData.user = $("input#user").val();
    formData.password = $("input#password").val();
    $.ajax({
        url: "rest.php/v1/user",
        type: "POST",
        dataType: "JSON",
        contentType: "application/json",
        data: JSON.stringify(formData),
        success: function(txt) {
            if (txt.status == "OK") {
                athTok = txt.token;
                $("div#error").css("display", "none");
                $("div#auth-content").css("display", "block");
                $("form#login-form").css("display", "none");
                $("#login-banner").css("display", "none");
                prepareDataPage();
            } else {
                $("div#error").css("display", "block");
                $("div#error").html("<p>" + txt.msg + "</p>");
            }
        },
        error: function(xhr) {
            alert("Invalid username or password");
        }
    });
}

function prepareDataPage() {
    populateButtons();
    getSummaryOfItems();
    getRecentItems();
}

function addItemAndUpdate(dt) {
    addDatabaseEntry(dt);
    getSummaryOfItems();
    getRecentItems();
}

function addDatabaseEntry(dt) {
    var updateData = {};
    updateData.token = athTok;
    updateData.itemPK = dt;
    $.ajax({
        url: "rest.php/v1/items",
        type: "POST",
        dataType: "JSON",
        data: JSON.stringify(updateData),
        success: function(txt) {
            if (txt.status == "FAIL") {
            } else {
            }
        },
        error: function(xhr, status, error) {
            var err = eval("(" + xhr.responseText + ")");
        }
    });
}

function populateButtons() {
    $.ajax({
        url: "rest.php/v1/items",
        type: "GET",
        dataType: "JSON",
        success: function(txt) {
            if (txt.status == "OK") {
                $.each(txt.items, function(i, item) {
                    $("div#buttons").append("<button class=\"btn btn-default\" onclick=\"addItemAndUpdate(this.id)\" id=\"" + item.pk + "\">" + item.item + "</button>");
                });
            } else {
            }
        },
        error: function(xhr) {
            alert("server error");
        }
    });
}

function getRecentItems() {
    $.ajax({
        url: "rest.php/v1/items/" + athTok,
        type: "GET",
        dataType: "json",
        success: function(txt) {
            if (txt.status == "OK") {
                $("#update-log-body").html(prepareUpdateLogTable(txt.items));
                $("div#error").css("display", "none");
                $("form#login-form").css("display", "none");
            } else {
                $("div#error").css("display", "block");
                console.log("Request failed: " + txt.msg);
            }
        },
        error: function(xhr, status, error) {
            alert("Error getting summary of items");
            var err = eval("(" + xhr.responseText + ")");
            console.log(err);
        }
    });
}

function prepareUpdateLogTable(data) {
    var tableMarkup = "";
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

function getSummaryOfItems() {
    $.ajax({
        url: "rest.php/v1/itemsSummary/" + athTok,
        type: "GET",
        dataType: "json",
        success: function(txt) {
            if (txt.status == "OK") {
                $.each(txt.itemCount, function(i, item) {
                    $("#diary-items-body").html(prepareSummaryOfItemsTable(txt.itemCount));
                });
                $("div#error").css("display", "none");
                $("form#login-form").css("display", "none");
            } else {
                $("div#error").css("display", "block");
                console.log("Request failed: " + txt.msg);
            }
        },
        error: function(xhr, status, error) {
            alert("Error getting summary of items");
            var err = eval("(" + xhr.responseText + ")");
            console.log(err);
        }
    });
}
