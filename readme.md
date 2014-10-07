Gallery
=======================

This extension allows you to create galleries from a specific folder in /files. It uses the title of the entry as folder

__Please check your settings in__ ```config.yml```

Keys
-------

* path
* name
* uploadDate
* model
* lens
* focalLength
* shutterSpeed
* fStop
* ISO
* time


Example
-------
for ```GalleryList()```


``` html
{% for image in GalleryList( record.slug , record.datecreated ) %}
<li>
    <a href="{{ image(image.path) }}" title="{{ image.name }}">
        <div class="images">
            <img src="{{ thumbnail( image.path, 300, 1200) }}" alt="" />
        </div>
        <div class="title">
            <h3>{{ image.name }}</h3>
        </div>
    </a>
</li>
{% endfor %}
```

for ```GalleryPreview()```

``` html
{% setcontent galleries = 'galleries/latest/6' %}
{% for gallery in galleries %}
{% set image = GalleryPreview( gallery.slug , gallery.datecreated ) %}
<li>
	<a href="{{ gallery.link }}" title="{{ gallery.title }}">
		<img src="{{ thumbnail(image, 263, 178 , 'c') }}" alt=" " />
	</a>
	<div class="title">
		<h3><a href="{{ gallery.link }}">{{ gallery.title }}</a></h3>
	</div>
</li>
{% endfor %}
```