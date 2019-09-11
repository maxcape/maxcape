let dyecolor = [0, 0, 0, 255];

let path = '/designer';
let vote_lock = false;

window.onload = function () {
    //Init all canvases
    init("mbasecanvas", "mbase");
    init("cbasecanvas", "cbase");
    for (let i = 1; i <= 4; i++) {
        init("mccanvas" + i, "mc" + i);
        init("cccanvas" + i, "cc" + i);
    }
    init("mtrimcanvas", "mtrim");
    init("ctrimcanvas", "ctrim");

    //Dye them default colors
    doDye(1);
    doDye(2);
    doDye(3);
    doDye(4);
};

function init(c, i) {
    //initialize canvas (c) with image (i)
    let canvas = document.getElementById(c);
    let context = canvas.getContext("2d");
    let image = document.getElementById(i);

    context.drawImage(image, 0, 0);
}


function RGBtoHSL(red, green, blue) {
    //Converts RGB colors to HSL colors

    let r = red / 255.0;
    let g = green / 255.0;
    let b = blue / 255.0;
    let H = 0;
    let S = 0;

    let min = Math.min(r, g, b);
    let max = Math.max(r, g, b);
    let delta = (max - min);

    let L = (max + min) / 2.0;

    if (delta == 0) {
        H = 0;
        S = 0;
    } else {
        S = L > 0.5 ? delta / (2 - max - min) : delta / (max + min);

        let dR = (((max - r) / 6) + (delta / 2)) / delta;
        let dG = (((max - g) / 6) + (delta / 2)) / delta;
        let dB = (((max - b) / 6) + (delta / 2)) / delta;

        if (r == max)
            H = dB - dG;
        else if (g == max)
            H = (1 / 3) + dR - dB;
        else
            H = (2 / 3) + dG - dR;

        if (H < 0)
            H += 1;
        if (H > 1)
            H -= 1;
    }
    let HSL = {hue: 0, sat: 0, bri: 0};
    HSL.hue = (H * 360);
    HSL.sat = (S * 100);
    HSL.bri = Math.round((L * 100));

    return HSL;
}

function convertRealValToJagex(val,rmax,jmax) {
    return Math.round(val/rmax*jmax);
}

function getRSHSL(color) {
    //Converts real HSL to RS HSL.

    let r = color[0], g = color[1], b = color[2];

    let hsl = RGBtoHSL(r, g, b);

    let rs = {hue: 0, sat: 0, bri: 0};

    rs.hue = hsl.hue > 0 ? convertRealValToJagex(hsl.hue, 360, 63) : 0;
    rs.sat = hsl.sat > 0 ? convertRealValToJagex(hsl.sat, 100, 7) : 0;
    rs.bri = hsl.bri > 0 ? convertRealValToJagex(hsl.bri, 100, 126) : 0;

    $("#rshue").val(rs.hue);
    $("#rssat").val(rs.sat);
    $("#rslit").val(rs.bri);
}

function doDye(number) {
    //Dye the layer (number).

    //Initialize the max/comp canvases
    let mcvs = document.getElementById("mccanvas" + number);
    let mctx = mcvs.getContext("2d");
    let ccvs = document.getElementById("cccanvas" + number);
    let cctx = ccvs.getContext("2d");

    //Get color and seperate out the values
    let colorhex = $("#color" + number).css("background-color");
    colorhex = colorhex.match(/[\d]+/g);

    dyecolor[0] = colorhex[0];
    dyecolor[1] = colorhex[1];
    dyecolor[2] = colorhex[2];

    //Get the RS HSL values.
    getRSHSL(dyecolor);

    //Set canvases to only color colored areas
    mctx.globalCompositeOperation = "source-atop";
    cctx.globalCompositeOperation = "source-atop";

    //Color the layer
    mctx.fillStyle = "rgba(" + dyecolor[0] + ", " + dyecolor[1] + ", " + dyecolor[2] + ", 1)";
    mctx.fillRect(0, 0, 244, 463);

    cctx.fillStyle = "rgba(" + dyecolor[0] + ", " + dyecolor[1] + ", " + dyecolor[2] + ", 1)";
    cctx.fillRect(0, 0, 244, 463);
}

$(".color").spectrum({
    color: "#f00",
    change: function(color) {
        renderColor(color, $(this));
        $(document).find("#savebtn").removeClass("disabled");
    },
    move: function(color) {
        renderColor(color, $(this));
        $(document).find("#savebtn").removeClass("disabled");
    }
});

$(".color").click(function () {
    let thiscolor = $(this);
    $(".color").each(function () {
        if ($(this).attr("id") === thiscolor.attr("id")) {
            if ($(this).hasClass("selected-cape-color")) {
                $(this).removeClass("selected-cape-color");
            } else {
                $(this).addClass("selected-cape-color");
                getColorData(thiscolor.attr("id"));
            }
        } else {
            $(this).removeClass("selected-cape-color");
        }
    })
});
$(document).click(function(e) {
    if (!$(e.target).is('.color')) {
        $('.color').removeClass("selected-cape-color");
    }
});
function getColorData(number) {
    //Get color and seperate out the values
    let colorhex = $("#" + number).css("background-color");
    colorhex = colorhex.match(/[\d]+/g);

    dyecolor[0] = colorhex[0];
    dyecolor[1] = colorhex[1];
    dyecolor[2] = colorhex[2];

    getRSHSL(dyecolor);
}

function displayCape(type) {
    let output = document.createElement("canvas");
    output.setAttribute("height", "425");
    output.setAttribute("width", type === "c" ? "221" : "238");

    let base = document.getElementById(type + "basecanvas"),
        c1 = document.getElementById(type + "ccanvas1"),
        c2 = document.getElementById(type + "ccanvas2"),
        c3 = document.getElementById(type + "ccanvas3"),
        c4 = document.getElementById(type + "ccanvas4"),
        trim = document.getElementById(type + "trimcanvas");

    let ctx = output.getContext("2d");

    ctx.drawImage(base, 0, 0);
    ctx.drawImage(c1, 0, 0);
    ctx.drawImage(c2, 0, 0);
    ctx.drawImage(c3, 0, 0);
    ctx.drawImage(c4, 0, 0);
    ctx.drawImage(trim, 0, 0);

    let img = output.toDataURL("image/png");
    let image = $("<img>").attr("src", img);

    $("#imageslot").empty().append(image);
    $('#cape-preview').modal();

    output.remove();
}

function getHSL(id) {
    let element = document.getElementById(id);
    let data_h = element.getAttribute("data-h");
    let data_s = element.getAttribute("data-s");
    let data_l = element.getAttribute("data-l");

    return {h: data_h, s: data_s, l: data_l};
}
//Handle colors (big)
$(".color").each(function (index) {
    //Loop through color divs
    $(this).bind("changedColor", function () {
        //Bind this event to each color so that when it's changed, we know which color to dye
        doDye(index + 1);
    });
});

$(document).on("click", '#vote-cape', function(data) {
    event.preventDefault();

    if (vote_lock) {
        return;
    }

    vote_lock = true;

    $.post(path+"/vote", {
        cape_id: $(this).data("cape"),
    }, function (data) {
        $('#vote_err').html(data);

        $.post('/designer/stats', function (data) {
            $('#stats').html(data);
            vote_lock = false;
        });
    });
});

function renderColor(color, context) {
    let colorSet = context.data('color');
    let element = $('#color' + colorSet);

    let hsl = color.toHsl();
    let rgb = color.toRgb();

    let h = Math.round(hsl.h);
    let s = Math.round(hsl.s * 100);
    let l = Math.round(hsl.l * 100);

    element.attr("data-h", h);
    element.attr("data-s", s);
    element.attr("data-l", l);
    element.css({'background-color': 'hsl(' + h + ', ' + s + '%, ' + l + '%)'});

    doDye(colorSet);

    let mcvs = document.getElementById("mccanvas" + colorSet);
    let mctx = mcvs.getContext("2d");
    let ccvs = document.getElementById("cccanvas" + colorSet);
    let cctx = ccvs.getContext("2d");

    mctx.globalCompositeOperation = "source-atop";
    cctx.globalCompositeOperation = "source-atop";

    mctx.fillStyle = "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", 1)";
    mctx.fillRect(0, 0, 244, 463);
    cctx.fillStyle = "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", 1)";
    cctx.fillRect(0, 0, 244, 463);
}