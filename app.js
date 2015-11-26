$('#fileupload').fileupload({
    forceIframeTransport: true,
    submit: function (e, data) {
        var that = $(this);
        // Post the current file meta data to your application,
        // to receive the YouTube upload url and token:
        // http://code.google.com/apis/youtube/1.0/developers_guide_python.html#BrowserUpload
        $.post('/your_application_url', data.files[0], function (result) {
            data.url = result.url;
            data.formData = {token: result.token};
            that.fileupload('send', data);
        });
        return false;
    }
});
