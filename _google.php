<?php
function google_create_date($dateobj) {
    // TODO: Use nullable operator
    if(property_exists($dateobj, "dateTime")) {
        return date_create($dateobj->dateTime);
    }
    else return date_create($dateobj->date);
}