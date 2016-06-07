##putObjectFromFile
- 如果出错可以用catch捕捉
- 成功返回
```javascript
{
  metadata:{
    contentType:,
    contentLength:,
    contentMd5:,
    date:,
    etag:    
  }
}
```

##listObjects
- 如果出错可以用catch捕捉
- 成功返回
```javascript
{
  name:/*buketname*/,
  prefix:,
  marker:,
  maxKeys:,
  isTruncated:,
  contents:[{
    key:,
    lastModified:,
    eTag:,
    size:,
    owner:{id:,displayName:,}  
  }],
  metadata:Array
}
```