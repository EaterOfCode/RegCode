RegCode
=======

Making regex easier with code

# Whats RegCode?

RegCode is here trying to make your life easier by taking control over Regex and make you write Regex by code thats readable instead of not understandable series of symbols

# Why RegCode?

Because Ive seen alot of people who don't understand Regex or/and can't read Regex and debugging regex is in this case much easier!

# How does it work?

by chaining calls. everycall adds an object to the list and from those calls it builds a string which is the regex

# Example

Let's give a simple example a username that isn't allowed to have less then 4 letters and may not start with a number.

Regex:

```javascript
/^[a-z][a-z0-9]{3,}$/i
```
  
RegCode (JavaScript):

```javascript
RC('i')
  .start()
  .range('a-z')
  .range('a-z0-9').repeat(3,Infinity)
  .end();
```

While the Regex is still readable in this case it may grow out into a disaster while RegCode will always be readable and less errornous.

# To do

* Create a final API for JavaScript
* Create a live action example for JavaScript
* Finish PHP
* Cover more langauges (C#, Java, Python)

This is all licensed by MIT
