
function confirm_upload(obj) {

    var filesize = 0;
    var Sys = {};

    if(navigator.userAgent.indexOf("MSIE")>0){
        Sys.ie=true;
    } else {
        Sys.notid=true;
    }

    if(typeof obj.files[0] == 'undefined') {
        return false;
    }

    if(Sys.notie){
        filesize = obj.files[0].size;
    } else if(Sys.ie) {
        try {
            obj.select();
            var realpath   = document.selection.createRange().text;
            var fileobject = new ActiveXObject ("Scripting.FileSystemObject");
            var filesize   = fileobject.GetFile(realpath).Size;
        } catch(e){
            alert(backupUploadLang.ACTIVE_X);
            return false;
        }
    }

    if(filesize > 3072*1024*1024) {
        alert(backupUploadLang.MAX_SIZE);
        return false;
    }

    filename = obj.files[0].name;

    var res = filename.match(/([^-]*)-(\d{14})-([^\.]*).tar/);
    if(res === null) {
        var res = filename.match(/(\d{8})-(\d{6})-(\d{10})-(.*).tgz/);
	if(res === null) {
        	alert(backupUploadLang.BAD_NAME);
        	return false;
	}
    }

    if (window.confirm(backupUploadLang.CONFIRM)) {
        var $hiddenInput = $('<input/>',{type:'hidden',name:'uploadbk',value:'uploadbk'});
        $hiddenInput.appendTo("#idformgrid");
        $("form#idformgrid" )
	.attr( "enctype", "multipart/form-data" )
	.attr( "encoding", "multipart/form-data" );
        document.getElementById('idformgrid').submit();
    }
}
