from urllib import request, parse
import os

import aiohttp
import asyncio
import imghdr

async def fetch(session, url):
	print('fetch ' + url)
	async with session.get(url) as response:
		try:
			return await response.read()
		# may be aiohttp.client_exceptions.ClientPayloadError
		except:
			return fetch(session, url)

async def download(url):
	o = parse.urlparse(url)
	if not os.path.isdir(o.netloc):
	  os.mkdir(o.netloc)
	  
	filename = o.path.replace('/', '_')
	if os.path.exists(o.netloc + '/' + filename):
		if imghdr.what(o.netloc + '/' + filename) == 'jpeg':
			return
	
	headers = {    
	#伪装一个chrome浏览器    
	"User-Agent":'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36'
	}
	async with aiohttp.ClientSession(headers=headers) as session:
		content = await fetch(session, url)
		f = open(o.netloc + '/' + filename, 'wb')
		f.write(content)

'''
async def download(url):
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
	  
	  '''

if __name__ =='__main__':	  
	
	loop = asyncio.get_event_loop()
	no = 1
	tasks = []
	while no < 142:
	  url = 'http://www.example.com/col/'
	  path = str(no) + '/001.jpg'	  
	  tasks += [download(url + path)]	 
	  no += 1 
	
	  
	loop.run_until_complete(asyncio.gather(*tasks))
	  
