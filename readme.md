# GPX Track

GPX Track allows you to embed maps into your blog posts showing GPX tracks. It also supports waypoints in the GPX file.

## Description

When I go hiking I track the route I take with my phone and take pictures. I
wanted a way to display a map of the walks I took on my blog complete with
where I took photos. This plugin is the result of that.

It registers a wordpress shortcode that takes the URL of a GPX file and
displays it using Google maps. Routes and tracks in the file show up as lines on
the map. Any named waypoints from the file are displayed as markers. It supports
a custom extension to GPX to add thumbnails for a waypoint. A tool is provided to
create these using Flickr.

## Installation

This section describes how to install the plugin and get it working.

e.g.

1. Copy the plugin's files to the `/wp-content/plugins/gpxtrack` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

You have to upload your GPX file somewhere useful. I use dropbox for mine. Then
just add the shortcode to embed the map in your blog post.

    [gpx url="<url to my gpx file>"]

If like me you take photos while out walking and upload those to flickr you can
use the new option in the tools menu of wordpress to embed your flickr photos into
your GPX file. Just upload your gpx file, provide your Flickr ID and it will
search for any matching photos and return a GPX file that includes them as
waypoints. These will show up on the map.
