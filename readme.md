# P System 

Web proxy in pure PHP!

## install:
In CloudFlare (or your DNS service), set your desired domain (e.g. ```domain.com```) to + ```*.domain.com```

Create a new apache virtualserver with the config ```web-proxy.conf``` (make sure to rename the server and the path of the files)

Start the virtual server. (```$ sudo a2ensite web-proxy.conf```)

Created certbot certificates for the new virtual server (```$ sudo certbot certonly --manual --preferred-challenges=dns --server https://acme-v02.api.letsencrypt.org/directory --agree-tos -d domain.com -d *.domain.com```)

Change the ```SERVER_BASE_DOMAIN``` field in ```include/config.php``` file

You can also add and modify users in the ```include/config.php``` file

Feel free to enjoy your web proxy 😁



### post Scriptum.

I would be very happy if you could star this project so that more people would enjoy it 👼