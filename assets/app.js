/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "./styles/app.scss";

// start the Stimulus application
//import "./bootstrap";

import "bootstrap";

//var $ = require("jquery");

// custom file input label change
$(".custom-file input").on("change", function (e) {
  if (e.target.files.length) {
    $(this).next(".custom-file-label").html(e.target.files[0].name);
  }
});
