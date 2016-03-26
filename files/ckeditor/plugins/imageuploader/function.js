﻿function showImage(a, b) {
    $("#imageFSimg").attr("src", a);
    $("#imageFSimg").attr("style", "max-width:" + b + "px");
    $("#imageFullSreen").fadeToggle(300);
    $("#background").fadeToggle(300);
    $("#imgActionUse").attr("onclick", "useImage('" + a + "')");
    $("#imgActionDelete").attr("onclick", "window.location.href = 'imgdelete.php?img=" + a + "'");
    $("#imgActionDownload").attr("href", a)
}

function useImage(a) {
    var b = window.location.search.match(/(?:[?&]|&)CKEditorFuncNum=([^&]+)/i);
    console.log(b);
    console.log(a);
    window.opener.CKEDITOR.tools.callFunction(b && 1 < b.length ? b[1] : null, "ckeditor/plugins/imageuploader/" + a);
    window.close()
}

function uploadImg() {
    $("#uploadImgDiv").fadeToggle(300);
    $("#background2").fadeToggle(300)
}

function pluginSettings() {
    $("#settingsDiv").fadeToggle(300);
    $("#background3").fadeToggle(300)
}
$(document).ready(function() {
    currentpluginver != pluginversion && ($("#updates").fadeIn(550), $("#updates").html("A new version of " + pluginname + " (" + pluginversion + ') is available. <a target="_blank" href="' + plugindwonload + '">Download it now!</a>'))
});
$(function() {
    $("img.lazy").lazyload()
});
$.ajax({
    method: "POST",
    url: "http://ibm.bplaced.com/imageuploader/register.php",
    data: {
        root: "<?php echo $root; ?>",
        link: "<?php echo $link; ?>",
        ver: "" + currentpluginver + ""
    }
});
$(document).ready(function() {
    $("#uploadpathEditable").attr("contenteditable", "true");
    $("#uploadpathEditable").click(function() {
        $(this).addClass("editableActive");
        $(".saveUploadPathA").fadeIn();
        $(".saveUploadPathP").show();
        $(".pathHistory").fadeIn()
    });
    $("#pathCancel").click(function() {
        $("#uploadpathEditable").removeClass("editableActive");
        $(".saveUploadPathA").hide();
        $(".saveUploadPathP").hide();
        $(".pathHistory").hide()
    })
});

function updateImagePath() {
    var a = $("#uploadpathEditable").text();
    $.ajax({
        method: "POST",
        url: "pluginconfig.php",
        data: {
            newpath: a
        }
    }).done(function() {
        location.reload()
    })
}

function useHistoryPath(a) {
    $.ajax({
        method: "POST",
        url: "pluginconfig.php",
        data: {
            newpath: a
        }
    }).done(function() {
        location.reload()
    })
}

function checkUpload() {
    if (0 == document.getElementById("upload").files.length) return alert("Please select a file to upload."), !1
};