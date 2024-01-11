function setTitle($title) { document.title = $title; }
formURL = "./";
function setOption(id, option) {
    if (option) {
        document.getElementById(id).value = option;
        var modeElement = document.querySelector('#' + id + 'fdSelect a[data-value="' + option + '"]');
        if (modeElement) { $('#' + id + 'dropdownMenuButton').text(modeElement.textContent); }
    }
}

$(document).ready(function () {
    $(".clickableRow tr").click(function () {
        var href = $(this).find("a").attr("href");
        if (href) { window.location = href; }
    });
});

function disableButton() { document.getElementById("login").disabled = true; }

function filterSelect(id, location = "") {
    var listItems = $('#' + id + 'fdSelect a');
    $('#' + id + 'filterInput').on('input', function () {
        var filterValue = $(this).val().toLowerCase();
        listItems.each(function () {
            var optionText = $(this).text().toLowerCase();
            if (optionText.includes(filterValue) || filterValue === '0') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    $('#' + id + 'fdSelect a').on('click', function (event) {
        event.preventDefault();
        $('#' + id + 'dropdownMenuButton').text($(this).text());
        $('#' + id).val($(this).data('value'));
        if (location != "") {
            window.location = location + "=" + $(this).data('value');
        }
    });
}

function get(url) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var responseData = JSON.parse(xhr.responseText);
            response(responseData);
            var loadingElement = document.getElementById("loading");
            if (loadingElement) {
                loadingElement.style.display = "none";
            }
        }
    };
    xhr.open("GET", formURL + url + "&m=api", true);
    xhr.send();
    document.getElementById("content").innerHTML = '<div id="loading"></div>';
}

function response(data) {
    data.forEach(function (view) {
        document.getElementById(view.id).innerHTML = view.content;
    });
}

$(document).on("click", "#dSubmit", function (event) {
    event.preventDefault();
    var formAction = $("#dynamicForm").attr("action");
    var outputElement = $("#response");
    if (!outputElement.length) {
      $("#dynamicForm").append('<div id="response"></div>');
    }
    var submitButton = $("#dSubmit");
    var loading = $("#loading");
    loading.removeAttr("style");
    submitButton.prop("disabled", true);
    var vResponse = $("#response");
    $.ajax({
      type: "POST",
      url: formAction,
      data: new FormData($("#dynamicForm")[0]),
      processData: false,
      contentType: false,
      success: function (response) {
        $("#sendCommand").val("");
        submitButton.prop("disabled", false);
        loading.hide();
        vResponse.removeClass();
        try {
          var responseData = JSON.parse(response);
          responseData.forEach(function (item) { $("#" + item.id).html(item.content); if (item.class) { $("#" + item.id).removeClass().addClass(item.class); } });
        } catch (error) { vResponse.html("Error: " + error); vResponse.removeClass().addClass("mt-4 alert alert-danger alert-dismissible fade show"); }
      },
      error: function (error) { submitButton.prop("disabled", false); $("#loading").hide(); $("#response").html("Error: " + error.statusText); $("#response").removeClass().addClass("mt-4 alert alert-danger alert-dismissible fade show"); }
    });
  });