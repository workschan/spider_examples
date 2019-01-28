package main

import (
	"fmt"
	"io/ioutil"
	"log"
	"math/rand"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
)

type Result struct {
	filename string
	content []byte
}

func main() {
	host := "https://cn.bing.com/images/trending?FORM=ILPTRD"
	body := Fetch(host)
	str := `<img.+?src="(https.+?)".+?>`
	reg := regexp.MustCompile(str)
	m := reg.FindAllStringSubmatch(string(body), -1)
	c := make(chan Result)
	wd, _ := os.Getwd()
	dir := wd + "/images/"
	err := filepath.Walk(dir, func(path string, info os.FileInfo, err error) error {
		if info.IsDir() {
			return nil
		}
		return os.Remove(path)
	})
	if err != nil {
		log.Println(err)
	}

	if _, err := os.Stat(dir); os.IsNotExist(err) {
		_ = os.Mkdir(dir, os.ModePerm)
	}

	for _, v := range m {
		go func(url string) {
			filename := dir + strconv.Itoa(rand.Int()) + ".jpg"
			c <- Result{filename, Fetch(url)}
		}(v[1])
	}

	for i :=0; i<len(m); i++ {
		r := <-c
		err := ioutil.WriteFile(r.filename, r.content, os.ModePerm)
		if err != nil {
			fmt.Println(err)
		}
	}
}

func Fetch(url string) []byte {
	log.Println(url)
	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		fmt.Println(err)
	}
	req.Header.Add("User-Agent", "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36")
	req.Header.Add("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8")
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		fmt.Println(err)
	}
	body, err := ioutil.ReadAll(resp.Body)
	if err != nil {
		fmt.Println(err)
	}
	return body
}

func GetFileContentType(out *os.File) (string, error) {

	// Only the first 512 bytes are used to sniff the content type.
	buffer := make([]byte, 512)

	_, err := out.Read(buffer)
	if err != nil {
		return "", err
	}

	// Use the net/http package's handy DectectContentType function. Always returns a valid
	// content-type by returning "application/octet-stream" if no others seemed to match.
	contentType := http.DetectContentType(buffer)

	return contentType, nil
}
