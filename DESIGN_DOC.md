# V1 Design Document – T-Shirt Site + Reddit Bot

## Goal
Launch a simple **Print-on-Demand T-shirt store** powered by **WordPress + WooCommerce + Printful**, with a **Reddit bot** that generates links to the store. The site will support dynamic query parameters so bot-generated links can pre-fill product details.

---

## Phase 1: Website

### 1. Infrastructure
- **Server**: AWS EC2 `t2.micro` (Ubuntu/Debian).
    - ✅ STATUS: DONE - Using BoolAlphaV2
- **Stack**: LAMP (Linux, Apache, MySQL, PHP)
    - Following: https://docs.aws.amazon.com/linux/al2023/ug/ec2-lamp-amazon-linux-2023.html
    - ✅ STATUS: DONE

- **Domain**: Point to EC2 instance.  
    - yourcommentonashirt.com
    - ✅ STATUS: DONE

- **SSL**: Use Let’s Encrypt with Certbot for HTTPS.
    - ✅ STATUS: DONE
    - Followed:
        - https://certbot.eff.org/instructions?ws=apache&os=pip
        - https://docs.aws.amazon.com/linux/al2023/ug/SSL-on-amazon-linux-2023.html
        - https://stackoverflow.com/questions/59549309/unable-to-find-a-virtual-host-listening-on-port-80-please-add-a-virtual-host

- **Setup MariaDB**
    - ✅ STATUS: DONE
    - Followed: https://docs.aws.amazon.com/linux/al2023/ug/ec2-lamp-amazon-linux-2023.html#secure-mariadb-lamp-server-2023

- **Setup PHPMyAdmin**
    - ✅ STATUS: DONE
    - Followed: https://docs.aws.amazon.com/linux/al2023/ug/ec2-lamp-amazon-linux-2023.html#install-phpmyadmin-lamp-server-2023

### 2. WordPress Setup
- **Install WordPress:**
    - ✅ STATUS: DONE
    - Followed: https://docs.aws.amazon.com/linux/al2023/ug/hosting-wordpress-aml-2023.html#hosting-wordpress-prereqs-2023
        - Install WordPress.  
        - Secure installation (disable XML-RPC, use fail2ban, harden `wp-config`).  
        - Configure database.  
        - Set up admin account with strong password.  

### 3. WooCommerce + Printful Integration
- Install **WooCommerce** plugin.  
    - ✅ STATUS: DONE
- Install **Printful plugin**.  
    - ✅ STATUS: DONE
- Connect Printful → WooCommerce.  
    - **ROADBLOCK**
        - Issue: Printful does not offer custom message out of the box. They give you, as the developer, the ability to precreate shirts then list them on the store, NOT dynamically alter the text as the user which is what we need to add any quote / username. 
        - Printful does not expose the design maker in any way through plugins etc, (well they kind of do but offer api mockup generator but not quite what I want) but there are projects that do this, the best of which appeared to be Lumise which is a code canyon plugin you can buy for Wordpress ($60) which then also offers an additional addon to integrate with Printful ($60). The add on would be perfect but the designer would require an additional button to change text which I don't love. Because all I need is text on a shirt and because I don't want to pay $120 there is better option:
        - Solution: Printful does offer an API that I can generate an order to, and I should be able to create the order with a custom image which is exactly what I need because I can then generate the image on order and upload the order in the printful backend.
            - WORK:
            - 1. Create an order with a custom image:
                - I have an image added to <domain>.com/generated/dot.png
                - Creating the order is not working, I need the product ID and the variant ID but I think I also need to have this product/variant added to my store? I then need to add the url of the custom image but I am getting 500 errors which don't make sense to me.
                GOT IT WORKING:
                **API V1:**
                `https://api.printful.com/orders`
                ```json
                {
                    //   "external_id": "123",
                    "shipping": "STANDARD",
                    "recipient": {
                        "name": "John Smith",
                        "company": "John Smith Inc",
                        "address1": "19749 Dearborn St",
                        "address2": "string",
                        "city": "Chatsworth",
                        "state_code": "CA",
                        "state_name": "California",
                        "country_code": "US",
                        "country_name": "United States",
                        "zip": "91311",
                        "phone": "2312322334",
                        "email": "firstname.secondname@domain.com",
                        "tax_number": "123.456.789-10"
                    },
                    "items": [
                        {
                        "id": 71,
                        //   "external_id": "Gildan-5000",
                        "variant_id": 4011,
                        "quantity": 1,
                        "price": "13.00",
                        "retail_price": "13.00",
                        //   "name": "Enhanced Matte Paper Poster 18×24",
                        "product": {
                            "variant_id": 3001,
                            "product_id": 301
                            // "image": "https://files.cdn.printful.com/products/71/5309_1581412541.jpg",
                            // "name": "Bella + Canvas 3001 Unisex Short Sleeve Jersey T-Shirt with Tear Away Label (White / 4XL)"
                        },
                        "files": [
                            {
                            "type": "default",
                            "url": "https://yourcommentonashirt.com/generated/dot.png",
                            "filename": "dot.png",
                            "visible": true,
                            "position": {
                                "area_width": 1800,
                                "area_height": 2400,
                                "width": 96,
                                "height": 64,
                                "top": 300,
                                "left": 0,
                                "limit_to_print_area": true
                            }
                            }
                        ]
                        }
                    ]
                }
                ```
                
                **API V2:**
                https://api.printful.com/v2/orders?store_id=16875297
                ```json
                {
                    "recipient": {
                        "name": "test",
                        "address1": "19749 Dearborn St",
                        "city": "Chatsworth",
                        "state_code": "CA",
                        "state_name": "California",
                        "country_code": "US",
                        "country_name": "United States",
                        "zip": "91311"
                    },
                    "order_items": [
                        {
                        "catalog_variant_id": 4011,
                        "source": "catalog",
                        "quantity": 1,
                        "placements": [
                            {
                            "placement": "front",
                            "technique": "dtg",
                            "layers": [
                                {
                                "type": "file",
                                "url": "https://www.printful.com/static/images/layout/printful-logo.png"
                                }
                            ]
                            }
                        ]
                        }
                    ]
                }
                ```



QUESTION: How can I tie this into the existing architecture/plugins?

- Currently everything goes through Woocommerce which then syncs with printful, but I need to use the printful API and I need to generate an image which will require overriding behavior of the printful plugin.
https://chatgpt.com/share/68d30003-dcc4-800a-bd6b-b6e7a841f38d

=> Have gotten image generation after order working

=> Have gotten call to api to create order working BUT ROADBLOCK:

Woocommerce is integrated with printful and every time I make an order it ties into the printful orders. I can only tie into products that I have integrated with the store in printful. I am generating an image in the backend dynamically that will be placed on top of the shirt, so I only know the file url at that point for my to add to the shirt. The issue is that I can only import shirts into my store that have designs precreated but can only make dynamic orders from the api. So I am left with the following choice:

Remove the `printful-shipping-for-woocommerce` plugin for the above reason as it is actually just making my workflow harder and offers no additional use as I can pass the shipping information along to printful myself. Also the plugin has officially been deprecated per the github page. So I am okay with removing this and heavily document our api implementation.



### 4. Required Pages
- **Landing / Buy Page**  
  - Product showcase.  
  - Query parameter handler:  
    - Parse `?comment=...&user=...&shirtColor=...&textColor=...&size=...`.  
    - Pre-fill WooCommerce product selection.  
    - Example:  
      ```
      https://yourstore.com/buy?comment=NicePost&user=redditUser123&shirtColor=black&textColor=white&size=medium
      ```
    - Store metadata (comment, user) as **custom order meta fields** in WooCommerce.  

- **Support Page**  
  - Static info + contact form (use WPForms or Contact Form 7).  

- **Reviews Page**  
  - WooCommerce product reviews enabled.  
  - Or simple testimonials plugin.  

- **How To Page**  
  - Static instructions on how to order (and maybe how the Reddit bot works).  

### 5. Plugins & Tools
- **Essential**:  
  - WooCommerce.  
  - Printful Integration.  
  - WPForms / Contact Form 7.  
  - Yoast SEO or Rank Math.  
  - WP Super Cache or W3 Total Cache.  
  - UpdraftPlus (backups).  

- **Security**:  
  - Wordfence or iThemes Security.  

### 6. Testing
- Verify:  
  - Products sync from Printful.  
  - Query params pre-fill fields.  
  - Checkout flow works (Stripe/PayPal).  
  - Orders go through Printful.  

---

## Phase 2: Reddit Bot

### 1. Requirements
- Monitor Reddit for mentions of `/u/YourBotName`.  
- Parse flags from comment body (`size: medium`, `shirtColor: black`, etc.).  
- Build URL:  
    ```
    https://yourstore.com/buy?comment=...&user=...&shirtColor=...&textColor=...&size=...
    ```
- Reply to comment with that link.  

### 2. Tech Stack
- **Language**: Python 3.  
- **Library**: PRAW (Reddit API wrapper).  
- **Hosting**: Same EC2 instance (separate process/service).  

### 3. Bot Workflow
1. Authenticate with Reddit API.  
2. Stream mentions from `reddit.inbox.stream()`.  
3. For each mention:  
 - Parse username, comment text.  
 - Extract flags via regex (`key: value`).  
 - Build store link with query params.  
 - Reply to comment.  
 - Mark as read.  

### 4. Deployment
- Run as a background service:  
- Use `systemd`, `supervisor`, or Docker.  
- Implement logging to file.  
- Add retry/backoff handling for API rate limits.  

### 5. Example Bot Reply
If a user comments:  
```
/u/examplebot size: medium shirtColor: black textColor: white
```
Bot replies:
```
Here’s your shirt link: https://yourstore.com/buy?comment=CoolDesign&user=RedditUser123&shirtColor=black&textColor=white&size=medium
```