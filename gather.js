const fs = require('fs');
const url = require("url");
const https = require('https');
const http = require('http');

const dir = 'temp/';
fs.access(dir, (err) => {
  if (err) {
    fs.mkdir(dir);
  }
});

let urls = [
  'http://pic.qiantucdn.com/58pic/16/95/40/79d58PICrIj_1024.jpg',
  'https://cbu01.alicdn.com/cms/upload/2016/883/878/2878388_1073447813.png',
  'http://mat1.gtimg.com/www/images/qq2012/qqLogoFilter.png'
];

for(let i in urls) {
  gather(urls[i]);
}

/**
 * Check if a whole png file.
 * @param data A Buffer object
 * @returns {boolean}
 */
function isPng(data) {
  return data.substr(-16) === '49454e44ae426082';
}

/**
 * Check if a whole jpg file.
 * @param data A Buffer object
 * @returns {boolean}
 */
function isJPG(data) {
  return data.substr(-4) === 'ffd9'; // JPG file end with ffd9 in hex.
}

/**
 * Check if a whole gif file.
 * @param data A Buffer object
 * @returns {boolean}
 */
function isGIF(data) {
  return data.substr(-2) === '3b';
}

function isImage(data) {
  data = data.toString('binary');
  data = data.replace(/\/\*.*\*\//, ''); // remove image's comments
  data = (Buffer.from(data, 'binary')).toString('hex');
  return isJPG(data) || isPng(data) || isGIF(data);
}

let retry = {};

function gather(urlString) {
  let u = url.parse(urlString);
  let protocol = u.protocol;
  let ihttp;
  if(protocol == 'https:') {
    ihttp = https;
  }else {
    ihttp = http;
  }
  let path = u.path;
  let filename = path.substr(1).replace(/\//g, '_');

  fs.readFile(dir + filename, (err, data) => {

    if(err || !isImage(data)) {
      ihttp.get(urlString, (res) => {

        console.log('Get ' + filename);

        const statusCode = res.statusCode;
        const contentType = res.headers['content-type'];

        let error;
        if (statusCode !== 200) {
          error = new Error(`Request Failed.\n` +
            `Status Code: ${statusCode}`);
        } else if (!/^image\/[jpeg|gif|png]/.test(contentType)) {
          error = new Error(`Invalid content-type.\n` +
            `Expected jpeg or gif or png but received ${contentType}`);
        }
        if (error) {
          console.log(error.message);
          // consume response data to free up memory
          res.resume();
          return;
        }

        res.setEncoding('binary');
        let rawData = '';
        res.on('data', (chunk) => {
          //console.log(`Received data size ${chunk.length} bytes.`);
          rawData += chunk;
        });
        res.on('end', () => {
          try {
            // Just retry 3 times
            retry.filename = retry.filename===undefined?0:retry.filename;
            if(!isImage(new Buffer(rawData, 'binary')) && retry.filename < 3) {
              console.log(`${filename} is not received completely!`);
              retry.filename++;
              gather(urlString);
            }else {
              fs.writeFile(dir + filename, rawData, 'binary', (err) => {
                if (err) throw err;
                console.log(`${filename} is saved!`);
              });
            }
          } catch (e) {
            console.log(e.message);
          }
        });
      }).on('error', (e) => {
        console.log(`Got error: ${e.message}`);
        gather(urlString);
      });
    }else {
      console.log(filename + ' had existed.')
    }
  });
}
