from urllib import request, parse
import os

url = 'https://www.baidu.com/img/bd_logo1.png'
o = parse.urlparse(url)

if not os.path.isdir(o.netloc):
  os.mkdir(o.netloc)
  
filename = o.path.replace('/', '_')
headers = {    
#伪装一个chrome浏览器    
"User-Agent":'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36'
}
req = request.Request(url=url, headers=headers)

with request.urlopen(req) as response:
  f = open(o.netloc + '/' + filename, 'wb')
  f.write(response.read())
