<?php
//if(!file_exists('include/db.php')) require_once('installer.php');
include "header.php";
//mysql_connect('localhost:8080', 'root', '') or die(mysql_error());
//mysql_select_db('mapa') or die(mysqli_error());
?>

<!DOCTYPE html>

<html>

<head>
  <!--
    This site was based on the Represent.LA project by:
    - Alex Benzer (@abenzer)
    - Tara Tiger Brown (@tara)
    - Sean Bonner (@seanbonner)

    Create a map for your startup community!
    https://github.com/abenzer/represent-map
    -->
  <title>Mapa Desbravalley - Comunidade Inovadora do Oeste Catarinense </title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <meta charset="UTF-8">
  <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:700|Open+Sans:400,700' rel='stylesheet' type='text/css'>
  <link href="./bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
  <link href="./bootstrap/css/bootstrap-responsive.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="map.css?nocache=289671982568" type="text/css" />
  <link rel="stylesheet" media="only screen and (max-device-width: 480px)" href="mobile.css" type="text/css" />
  <script src="./scripts/jquery-1.7.1.js" type="text/javascript" charset="utf-8"></script>
  <script src="./bootstrap/js/bootstrap.js" type="text/javascript" charset="utf-8"></script>
  <script src="./bootstrap/js/bootstrap-typeahead.js" type="text/javascript" charset="utf-8"></script>
  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>



  <script type="text/javascript" src="./scripts/label.js"></script>

  <script type="text/javascript">
    var map;
    var infowindow = null;
    var gmarkers = [];
    var markerTitles = [];
    var highestZIndex = 0;
    var agent = "default";
    var zoomControl = true;


    // detect browser agent
    $(document).ready(function() {
      $("#msg").css("display", "none");
      if (navigator.userAgent.toLowerCase().indexOf("iphone") > -1 || navigator.userAgent.toLowerCase().indexOf("ipod") > -1) {
        agent = "iphone";
        zoomControl = false;
      }
      if (navigator.userAgent.toLowerCase().indexOf("ipad") > -1) {
        agent = "ipad";
        zoomControl = false;
      }
    });


    // resize marker list onload/resize
    $(document).ready(function() {
      resizeList()
    });
    $(window).resize(function() {
      resizeList();
    });

    // resize marker list to fit window
    function resizeList() {
      newHeight = $('html').height() - $('#topbar').height();
      $('#list').css('height', newHeight + "px");
      $('#menu').css('margin-top', $('#topbar').height());
    }


    // initialize map
    function initialize() {
      // set map styles
      var mapStyles = [{
        featureType: "road",
        elementType: "geometry",
        stylers: [{
            hue: "#8800ff"
          },
          {
            lightness: 100
          }
        ]
      }, {
        featureType: "road",
        stylers: [{
            visibility: "on"
          },
          {
            hue: "#91ff00"
          },
          {
            saturation: -62
          },
          {
            gamma: 1.98
          },
          {
            lightness: 45
          }
        ]
      }, {
        featureType: "water",
        stylers: [{
            hue: "#005eff"
          },
          {
            gamma: 0.72
          },
          {
            lightness: 42
          }
        ]
      }, {
        featureType: "transit.line",
        stylers: [{
          visibility: "off"
        }]
      }, {
        featureType: "administrative.locality",
        stylers: [{
          visibility: "on"
        }]
      }, {
        featureType: "administrative.neighborhood",
        elementType: "geometry",
        stylers: [{
          visibility: "simplified"
        }]
      }, {
        featureType: "landscape",
        stylers: [{
            visibility: "on"
          },
          {
            gamma: 0.41
          },
          {
            lightness: 46
          }
        ]
      }, {
        featureType: "administrative.neighborhood",
        elementType: "labels.text",
        stylers: [{
            visibility: "on"
          },
          {
            saturation: 33
          },
          {
            lightness: 20
          }
        ]
      }];

      // set map options
      var myOptions = {
        zoom: 7,
        //minZoom: 10,
        center: new google.maps.LatLng(<?= $lat_lng ?>),
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        streetViewControl: false,
        mapTypeControl: false,
        panControl: false,
        zoomControl: zoomControl,
        styles: mapStyles,
        zoomControlOptions: {
          style: google.maps.ZoomControlStyle.SMALL,
          position: google.maps.ControlPosition.LEFT_CENTER
        }
      };
      map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
      zoomLevel = map.getZoom();

      // prepare infowindow
      infowindow = new google.maps.InfoWindow({
        content: "holding..."
      });

      // only show marker labels if zoomed in
      google.maps.event.addListener(map, 'zoom_changed', function() {
        zoomLevel = map.getZoom();
        if (zoomLevel <= 15) {
          $(".marker_label").css("display", "none");
        } else {
          $(".marker_label").css("display", "inline");
        }
      });

      // markers array: name, type (icon), lat, long, description, uri, address
      markers = new Array();
      <?php
      $types = array(
        array('startup', 'Startups'),
        array('accelerator', 'Acelaradoras'),
        array('incubator', 'Incubadoras'),
        array('coworking', 'Coworking'),
        array('investor', 'Investidores'),
        array('service', 'Outros'),
      );
      $marker_id = 0;
      foreach ($types as $type) {
        $places = mysql_query("SELECT * FROM places WHERE approved='1' AND type='$type[0]' ORDER BY title");
        $places_total = mysql_num_rows($places);
        while ($place = mysql_fetch_assoc($places)) {
          $place['title'] = htmlspecialchars_decode(addslashes(htmlspecialchars($place['title'])));
          $place['description'] = str_replace(array("\n", "\t", "\r"), "", htmlspecialchars_decode(addslashes(htmlspecialchars($place['description']))));
          $place['uri'] = addslashes(htmlspecialchars($place['uri']));
          $place['address'] = htmlspecialchars_decode(addslashes(htmlspecialchars($place['address'])));
          //$place['title'] = htmlspecialchars_decode(addslashes(htmlspecialchars($place['type'])));
          echo "
                markers.push(['" . $place['title'] . "', '" . $place['type'] . "', '" . $place['lat'] . "', '" . $place['lng'] . "', '" . $place['description'] . "', '" . $place['uri'] . "', '" . $place['address'] . "']);
                markerTitles[" . $marker_id . "] = '" . $place['title'] . "';
              ";
          //$count[$place['type']]++;
          $marker_id++;
        }
      }
      if ($show_events == true) {
        $place[type] = "event";
        $events = mysql_query("SELECT * FROM events WHERE start_date > " . time() . " AND start_date < " . (time() + 9676800) . " ORDER BY id DESC");
        $events_total = mysql_num_rows($events);
        while ($event = mysql_fetch_assoc($events)) {
          $event[title] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[title])));
          $event[description] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[description])));
          $event[uri] = addslashes(htmlspecialchars($event[uri]));
          $event[address] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[address])));
          $event[start_date] = date("D, M j @ g:ia", $event[start_date]);
          echo "
                markers.push(['" . $event[title] . "', 'event', '" . $event[lat] . "', '" . $event[lng] . "', '" . $event[start_date] . "', '" . $event[uri] . "', '" . $event[address] . "']);
                markerTitles[" . $marker_id . "] = '" . $event[title] . "';
              ";
          $count[$place[type]]++;
          $marker_id++;
        }
      }
      ?>

      // add markers
      jQuery.each(markers, function(i, val) {
        infowindow = new google.maps.InfoWindow({
          content: ""
        });

        // offset latlong ever so slightly to prevent marker overlap
        rand_x = Math.random();
        rand_y = Math.random();
        val[2] = parseFloat(val[2]) + parseFloat(parseFloat(rand_x) / 6000);
        val[3] = parseFloat(val[3]) + parseFloat(parseFloat(rand_y) / 6000);

        // show smaller marker icons on mobile
        if (agent == "iphone") {
          var iconSize = new google.maps.Size(16, 19);
        } else {
          iconSize = null;
        }

        // build this marker
        var markerImage = new google.maps.MarkerImage("./images/icons/" + val[1] + ".png", null, null, null, iconSize);
        var marker = new google.maps.Marker({
          position: new google.maps.LatLng(val[2], val[3]),
          map: map,
          title: '',
          clickable: true,
          infoWindowHtml: '',
          zIndex: 10 + i,
          icon: markerImage
        });
        marker.type = val[1];
        gmarkers.push(marker);

        // add marker hover events (if not viewing on mobile)
        if (agent == "default") {
          google.maps.event.addListener(marker, "mouseover", function() {
            this.old_ZIndex = this.getZIndex();
            this.setZIndex(9999);
            $("#marker" + i).css("display", "inline");
            $("#marker" + i).css("z-index", "99999");
          });
          google.maps.event.addListener(marker, "mouseout", function() {
            if (this.old_ZIndex && zoomLevel <= 15) {
              this.setZIndex(this.old_ZIndex);
              $("#marker" + i).css("display", "none");
            }
          });
        }

        // format marker URI for display and linking
        var markerURI = val[5];
        if (markerURI.substr(0, 7) != "http://") {
          markerURI = "http://" + markerURI;
        }
        var markerURI_short = markerURI.replace("http://", "");
        var markerURI_short = markerURI_short.replace("www.", "");

        // add marker click effects (open infowindow)
        google.maps.event.addListener(marker, 'click', function() {
          infowindow.setContent(
            "<div class='marker_title'>" + val[0] + "</div>" +
            "<div class='marker_uri'><a target='_blank' href='" + markerURI + "'>" + markerURI_short + "</a></div>" +
            "<div class='marker_desc'>" + val[4] + "</div>" +
            "<div class='marker_address'>" + val[6] + "</div>"
          );
          infowindow.open(map, this);
        });

        // add marker label
        var latLng = new google.maps.LatLng(val[2], val[3]);
        var label = new Label({
          map: map,
          id: i
        });
        label.bindTo('position', marker);
        label.set("text", val[0]);
        label.bindTo('visible', marker);
        label.bindTo('clickable', marker);
        label.bindTo('zIndex', marker);
      });


      // zoom to marker if selected in search typeahead list
      $('#search').typeahead({
        source: markerTitles,
        onselect: function(obj) {
          marker_id = jQuery.inArray(obj, markerTitles);
          if (marker_id > -1) {
            map.panTo(gmarkers[marker_id].getPosition());
            map.setZoom(15);
            google.maps.event.trigger(gmarkers[marker_id], 'click');
          }
          $("#search").val("");
        }
      });
    }


    // zoom to specific marker
    function goToMarker(marker_id) {
      if (marker_id) {
        map.panTo(gmarkers[marker_id].getPosition());
        map.setZoom(15);
        google.maps.event.trigger(gmarkers[marker_id], 'click');
      }
    }

    // toggle (hide/show) markers of a given type (on the map)
    function toggle(type) {
      if ($('#filter_' + type).is('.inactive')) {
        show(type);
      } else {
        hide(type);
      }
    }

    // hide all markers of a given type
    function hide(type) {
      for (var i = 0; i < gmarkers.length; i++) {
        if (gmarkers[i].type == type) {
          gmarkers[i].setVisible(false);
        }
      }
      $("#filter_" + type).addClass("inactive");
    }

    // show all markers of a given type
    function show(type) {
      for (var i = 0; i < gmarkers.length; i++) {
        if (gmarkers[i].type == type) {
          gmarkers[i].setVisible(true);
        }
      }
      $("#filter_" + type).removeClass("inactive");
    }

    // toggle (hide/show) marker list of a given type
    function toggleList(type) {
      $("#list .list-" + type).toggle();
    }


    // hover on list item
    function markerListMouseOver(marker_id) {
      $("#marker" + marker_id).css("display", "inline");
    }

    function markerListMouseOut(marker_id) {
      $("#marker" + marker_id).css("display", "none");
    }

    google.maps.event.addDomListener(window, 'load', initialize);
  </script>

  <? echo $head_html; ?>
</head>

<body>

  <!-- display error overlay if something went wrong -->


  <!-- facebook like button code -->
  <div id="fb-root"></div>
  <script>
    (function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s);
      js.id = id;
      js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=421651897866629";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
  </script>

  <!-- google map -->
  <div id="map_canvas"></div>

  <!-- topbar -->
  <div class="topbar" id="topbar">
    <div class="wrapper">
      <div class="right">
        <div class="buttons">


          <a href="#modal_add" target="_blank" class="btn btn-success" data-toggle="modal"><i class="icon-plus-sign icon-white"></i>Cadastre-se</a>

          <a href="#modal_info" class="btn btn-large btn-info" data-toggle="modal"><i class="icon-info-sign icon-white"></i>Sobre o Mapa</a>

        </div>
        <div class="share">
          <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?= $domain ?>" data-text="<?= $twitter['share_text'] ?>" data-count="none">Tweet</a>
          <script>
            ! function(d, s, id) {
              var js, fjs = d.getElementsByTagName(s)[0];
              if (!d.getElementById(id)) {
                js = d.createElement(s);
                js.id = id;
                js.src = "//platform.twitter.com/widgets.js";
                fjs.parentNode.insertBefore(js, fjs);
              }
            }(document, "script", "twitter-wjs");
          </script>
          <div class="fb-like" data-href="<?= $domain ?>" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false" data-font="arial"></div>
        </div>
      </div>
      <div class="left">
        <div class="logo">
          <a href="./">
            <img src="images/logo_.png" alt="" />
          </a>
        </div>

        <!--   <div class="search"> -->
        <!-- <input type="text" name="search" id="search" placeholder="Procure uma Empresa..." data-provide="typeahead" autocomplete="off" /> -->
        <!--  </div> -->
      </div>
    </div>
  </div>

  <!-- right-side gutter -->
  <div class="menu" id="menu">
    <ul class="list" id="list">
      <?php
      $types = array(
        array('startup', 'Startups'),
        array('accelerator', 'Aceleradoras'),
        array('incubator', 'Incubadoras'),
        array('coworking', 'Coworking'),
        array('investor', 'Investidores'),
        array('service', 'Outros'),
      );
      if ($show_events == true) {
        $types[] = array('event', 'Events');
      }
      $marker_id = 0;
      foreach ($types as $type) {
        if ($type[0] != "event") {
          $markers = mysql_query("SELECT * FROM places WHERE approved='1' AND type='$type[0]' ORDER BY title");
        } else {
          $markers = mysql_query("SELECT * FROM events WHERE start_date > " . time() . " AND start_date < " . (time() + 4838400) . " ORDER BY id DESC");
        }
        $markers_total = mysql_num_rows($markers);
        echo "
              <li class='category'>
                <div class='category_item'>
                  <div class='category_toggle' onClick=\"toggle('$type[0]')\" id='filter_$type[0]'></div>
                  <a href='#' onClick=\"toggleList('$type[0]');\" class='category_info'><img src='./images/icons/$type[0].png' alt='' />$type[1]<span class='total'> ($markers_total)</span></a>
                </div>
                <ul class='list-items list-$type[0]'>
            ";
        while ($marker = mysql_fetch_assoc($markers)) {
          echo "
                  <li class='" . $marker['type'] . "'>
                    <a href='#' onMouseOver=\"markerListMouseOver('" . $marker_id . "')\" onMouseOut=\"markerListMouseOut('" . $marker_id . "')\" onClick=\"goToMarker('" . $marker_id . "');\">" . $marker['title'] . "</a>
                  </li>
              ";
          $marker_id++;
        }
        echo "
                </ul>
              </li>
            ";
      }
      ?>
      <li class="blurb"><?= $blurb ?></li>
      <li class="attribution">
        <!-- per our license, you may not remove this line -->
        <?= $attribution ?>
      </li>
    </ul>
  </div>

  <!-- more info modal -->
  <div class="modal hide" id="modal_info">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">×</button>
      <h3>Sobre este Mapa</h3>
    </div>
    <div class="modal-body">
      <p>
        Este mapa foi criado para conectar e promover a Comunidade Inovadora do Oeste de SC - Desbravalley. A ideia foi lançada! Agora você pode adicionar sua empresa e convidar seus parceiros para
        <?php if ($sg_enabled) { ?>
          <a href="#modal_add" data-toggle="modal" data-dismiss="modal">participarem desta iniciativa.</a>
        <?php } else { ?>
          <a href="#modal_add" data-toggle="modal" data-dismiss="modal">participarem desta iniciativa.</a>
        <?php } ?>
      </p>


    </div>
    <div class="modal-footer">
      <a href="#" class="btn" data-dismiss="modal" style="float: right;">Fechar</a>
    </div>
  </div>


  <!-- add something modal -->
  <div class="modal hide" id="modal_add">




    <form action="add.php" id="modal_addform" class="form-horizontal">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Adicione alguma coisa!</h3>
      </div>


      <div class="modal-body">

        <p>
          Quer adicionar sua empresa neste mapa? Preencha o formul&aacute;rio que n&oacute;s vamos revisar o mais r&aacute;pido poss&iacute;vel!
        </p>
        <div id="result"></div>
        <fieldset>
          <div class="control-group">
            <label class="control-label" for="add_owner_name">Seu Nome</label>
            <div class="controls">
              <input type="text" class="input-xlarge" name="owner_name" id="add_owner_name" maxlength="100" required>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label" for="add_owner_email">Seu Email</label>
            <div class="controls">
              <input type="email" class="input-xlarge" name="owner_email" id="add_owner_email" maxlength="100" required>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label" for="add_title">Nome da Empresa</label>
            <div class="controls">
              <input type="text" class="input-xlarge" name="title" id="add_title" maxlength="100" autocomplete="off" required>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label" for="input01">Tipo da empresa</label>
            <div class="controls">
              <select name="type" id="add_type" class="input-xlarge">
                <option value="startup">Startup</option>
                <option value="accelerator">Aceleradora</option>
                <option value="incubator">Incubadora</option>
                <option value="coworking">Coworking</option>
                <option value="investor">VC/Angel</option>
                <!--                  <option value="spinoff">Spin-off</option>-->
                <option value="others">Outros</option>
                <!--<option value="service">Empresa de Consultoria</option>
                  <option value="hackerspace">Hackerspace</option>-->
              </select>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label" for="add_address">Endere&ccedil;o</label>
            <div class="controls">
              <input type="text" class="input-xlarge" name="address" id="add_address" required>
              <p class="help-block">
                Deve ser o <b>seu endere&ccedil;o completo (incluindo cidade e CEP)</b>.
                Se funciona no Google Maps, ele vai funcionar aqui.
              </p>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label" for="add_uri">Website URL</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="add_uri" name="uri" placeholder="http://" required>
              <p class="help-block">
                A sua URL deve ser completo, por exemplo: "http://www.linkdositedesbravalley.com.br"
              </p>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label" for="add_description">Descri&ccedil;&atilde;o</label>
            <div class="controls">
              <input type="text" class="input-xlarge" id="add_description" name="description" maxlength="150" required>
              <p class="help-block">
                Descri&ccedil;&atilde;o, breve e conciso. Qual &eacute; o seu produto? Que problema voc&ecirc; resolve? Max 150 caracteres.
              </p>
            </div>
          </div>
        </fieldset>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Enviar para revis&atilde;o</button>
        <a href="#" class="btn" data-dismiss="modal" style="float: right;" onclick="changeFormJs();">Fechar</a>
      </div>
    </form>
  </div>
  <script>
    function changeFormJs() {
      console.log("FUNÇÃO");
      $("#modal_addform p").css("display", "visible");
      $("#modal_addform fieldset").css("display", "visible");
      $("#modal_addform .btn-primary").css("display", "visible");
      $("#msg").css("display", "none");
    }
    // add modal form submit
    $("#modal_addform").submit(function(event) {
      event.preventDefault();
      // get values
      var $form = $(this),
        owner_name = $form.find('#add_owner_name').val(),
        owner_email = $form.find('#add_owner_email').val(),
        title = $form.find('#add_title').val(),
        type = $form.find('#add_type').val(),
        address = $form.find('#add_address').val(),
        uri = $form.find('#add_uri').val(),
        description = $form.find('#add_description').val(),
        url = $form.attr('action');

      // send data and get results
      $.post(url, {
          owner_name: owner_name,
          owner_email: owner_email,
          title: title,
          type: type,
          address: address,
          uri: uri,
          description: description
        },
        function(data) {
          var content = $(data).find('#content');

          // if submission was successful, show info alert
          if (data == "success") {

            




            document.getElementById("add_owner_name").value = "";
            document.getElementById("add_owner_email").value = "";
            document.getElementById("add_address").value = "";
            document.getElementById("add_uri").value = "";
            document.getElementById("add_description").value = "";
            document.getElementById("add_title").value = "";
            alert("Recebemos o seu envio e iremos analisá-lo em breve. Obrigado!");

            //$("#modal_addform #result").html("Recebemos o seu envio e iremos analisá-lo em breve. Obrigado!");
            //$("#modal_addform #result").addClass("alert alert-info");
            //$("#msg").css("display", "visible");
            //$("#modal_addform p").css("display", "none");
            //$("#modal_addform fieldset").css("display", "none");
            //$("#modal_addform .btn-primary").css("display", "none");

            // if submission failed, show error
          } else {
            $("#modal_addform #result").html(data);
            $("#modal_addform #result").addClass("alert alert-danger");
          }
        }
      );
    });
  </script>

</body>

</html>