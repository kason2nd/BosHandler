// function BosHandler($container,ThumbImgClassName,msize,addItem){

// }

var maxsize=4*1024*1024;

var $container;
var ThumbImgClassName;
var addItem;

var fileReader;
var fileList;
var $itemList;
function init($con,imgClassName,addfun,msize){
  $container=$con;
  ThumbImgClassName=imgClassName;
  addItem=addfun;
  if(msize)
    maxsize=msize;
  fileReader=getUploadFileReader();
}

function getThumb(name,data){
  var $img =  $('<img>');
  $img.prop("src",data); //是Base64的data url数据
  $img.attr("FileName",name);
  $img.addClass(ThumbImgClassName);
  return $img;
}


function readFileItems(allfilelist){
  $itemList=$container.find('.'+ThumbImgClassName);
  fileList=[];
  for(var i=0; i<allfilelist.length;i++){
    if(allfilelist[i].size<=maxsize){
      fileList.push(allfilelist[i]);
    }else{
      console.log("too large");
    }
  }
  if(fileList.length>0){
    fileReader.readAsDataURL(fileList[fileList.length-1]);
  }
  else
    console.log("nothing to read");
}

function getUploadFileReader(){
  var freader = new FileReader();
  freader.onload = function(evt){  
    var readingName=fileList[fileList.length-1].name;
    var existed=false;
    var dataUrl=this.result;
    for(var i in $itemList){
      if($itemList.eq(i).attr("FileName")==readingName){
        console.log("name equal");
        if(dataUrl==$itemList.eq(i).prop("src")){
          console.log("data equal");
          existed=true;
          break;
        }
      }
    }
    if(existed==true){
      console.log("already exist");
    }else{
      addItem(readingName,dataUrl);
    }

    fileList.pop();
    if(fileList.length>0){
      freader.readAsDataURL(fileList[fileList.length-1]);
    }
  }
  return freader;
}



function uploadFile(formjson,progressCallback){
  var formData = new FormData();
  for(var key in formjson){
    formData.append(key , formjson[key]);
  }
  return $.ajax({
    type: "POST",
    url: "http://weddingdress.duapp.com/bos/upload.php",
    data: formData ,
    processData : false, //必须false才会避开jQuery对 formdata 的默认处理 ,XMLHttpRequest会对 formdata 进行正确的处理 
    contentType : false ,  //必须false才会自动加上正确的Content-Type 
    xhr: function(){
      var xhr = $.ajaxSettings.xhr();
      if(progressCallback && xhr.upload) {
        xhr.upload.addEventListener("progress" , progressCallback, false);
        return xhr;
      }
    } 
  });
}