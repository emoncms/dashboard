/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@gmail.com
    Enhancements done by: Andreas Messerli firefox7518@gmail.com
    kWhPeriod widget implemented by: Daniel Bates dgbates@mail.uk
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

function addOption (
  widget,
  optionKey,
  optionType,
  optionName,
  optionHint,
  optionData
) {
  widget['options'].push(optionKey)
  widget['optionstype'].push(optionType)
  widget['optionsname'].push(optionName)
  widget['optionshint'].push(optionHint)
  widget['optionsdata'].push(optionData)
}

function kwhperiod_widgetlist () {
  var widgets = {
    kwhperiod: {
      offsetx: -40,
      offsety: -30,
      width: 120,
      height: 60,
      menu: 'Widgets',
      options: [],
      optionstype: [],
      optionsname: [],
      optionshint: [],
      optionsdata: []
    }
  }

  var decimalsDropBoxOptions = [
    // Options for the type combobox. Each item is [typeID, "description"]
    [-1, _Tr('Automatic')],
    [0, '0'],
    [1, '1'],
    [2, '2'],
    [3, '3'],
    [4, '4'],
    [5, '5'],
    [6, '6']
  ]

  var lastYearDropBoxOptions = [
    // Options for the type combobox. Each item is [typeID, "description"]
    [0, _Tr('False')],
    [1, _Tr('True')]
  ]

  var quantisationDropBoxOptions = [
    // Options for the type combobox. Each item is [typeID, "description"]
    [0, _Tr('False')],
    [1, _Tr('True')]
  ]

  var periodDropBoxOptions = [
    // Options for the type combobox. Each item is [typeID, "description"]
    [0, 'Hour (usage in hour just been)'],
    [1, 'Day (since midnight)'],
    [2, 'Week (7 days exactly'],
    [3, 'Month (Calendar month, average/day)'],
    [4, 'Year (usage in one year exactly)']
  ]

  var fontoptions = [
    [9, 'Arial Black'],
    [8, 'Arial Narrow'],
    [7, 'sans-serif'],
    [6, 'Helvetica Neue'],
    [5, 'Helvetica'],
    [4, 'Comic Sans MS'],
    [3, 'Courier New'],
    [2, 'Arial'],
    [1, 'Georgia'],
    [0, 'Impact']
  ]

  var fstyleoptions = [
    [2, _Tr('Normal')],
    [1, _Tr('Italic')],
    [0, _Tr('Oblique')]
  ]

  var fweightoptions = [
    [1, _Tr('Bold')],
    [0, _Tr('Normal')]
  ]

  var sizeoptions = [
    [14, '18'], // set size 18 to the top position to be the default value for creating new kwhperiod widgets otherwise size 40 would be always the default
    [13, '40'],
    [12, '36'],
    [11, '32'],
    [10, '28'],
    [9, '24'],
    [8, '22'],
    [7, '20'],
    [6, '18'],
    [5, '16'],
    [4, '14'],
    [3, '12'],
    [2, '10'],
    [1, '8'],
    [0, '6']
  ]

  var alignmentOptions = [
    ['center', _Tr('Center')],
    ['left', _Tr('Left')],
    ['right', _Tr('Right')]
  ]

  addOption(
    widgets['kwhperiod'],
    'feedid',
    'feedid',
    _Tr('Feed'),
    _Tr('Feed value'),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'period_length',
    'dropbox',
    _Tr('Period length'),
    _Tr('Period length'),
    periodDropBoxOptions
  )
  addOption(
    widgets['kwhperiod'],
    'period_multiplier',
    'value',
    _Tr('Period multiplier'),
    _Tr(
      'Number of Periods, this defines the width of the time window. 0 and 1 default to length of period.'
    ),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'periods_ago',
    'value',
    _Tr('Periods ago'),
    _Tr(
      'Periods ago, for past periods. Shifts time window into the past by units of hour/day/month etc.. Set to Zero for current period only (i.e. today). Hour is reset on the hour, day reset at midnight, week reset on Monday, month on the 1st of the month, Year from the 1st January.'
    ),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'period_offset',
    'value',
    _Tr('Period offset'),
    _Tr(
      'Period start offset in seconds, positive number shifts time forwards.'
    ),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'period_quantisation',
    'dropbox',
    _Tr('Period quantisation'),
    _Tr(
      'Set to 1 for the time window to start at the beginning of the period length, i.e. hour goes from x:00 minutes, day from 00:00 hours, week from Monday 00:00, Month from 1st of the Month 00:00, and Year from 1st of January 00:00. No quantisation will result in a time window strictly from the present moment, i.e today at 13:30 to yesterday at 13:30.'
    ),
    quantisationDropBoxOptions
  )
  addOption(
    widgets['kwhperiod'],
    'use_last_year',
    'dropbox',
    _Tr("Use last year's data"),
    _Tr("Set to True to use last year's data"),
    lastYearDropBoxOptions
  )
  addOption(
    widgets['kwhperiod'],
    'prepend',
    'value',
    _Tr('Prepend Text'),
    _Tr('Prepend Text'),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'append',
    'value',
    _Tr('Append Text'),
    _Tr('Append Text (Units)'),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'decimals',
    'dropbox',
    _Tr('Decimals'),
    _Tr('Decimals to show'),
    decimalsDropBoxOptions
  )
  addOption(
    widgets['kwhperiod'],
    'colour',
    'colour_picker',
    _Tr('Colour'),
    _Tr('Colour used for display'),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'font',
    'dropbox',
    _Tr('Font'),
    _Tr('Font used for display'),
    fontoptions
  )
  addOption(
    widgets['kwhperiod'],
    'fstyle',
    'dropbox',
    _Tr('Font style'),
    _Tr('Font style used for display'),
    fstyleoptions
  )
  addOption(
    widgets['kwhperiod'],
    'fweight',
    'dropbox',
    _Tr('Font weight'),
    _Tr('Font weight used for display'),
    fweightoptions
  )
  addOption(
    widgets['kwhperiod'],
    'size',
    'dropbox',
    _Tr('Size'),
    _Tr('Text size in px to use'),
    sizeoptions
  )
  addOption(
    widgets['kwhperiod'],
    'align',
    'dropbox',
    _Tr('Alignment'),
    _Tr('Alignment'),
    alignmentOptions
  )
  addOption(
    widgets['kwhperiod'],
    'timeout',
    'value',
    _Tr('Timeout'),
    _Tr('Timeout without feed update in seconds (empty is never)'),
    []
  )
  addOption(
    widgets['kwhperiod'],
    'errormessagedisplayed',
    'value',
    _Tr('Error Message'),
    _Tr('Error message displayed when timeout is reached'),
    []
  )

  return widgets
}

function draw_kwhperiod (
  kwhperiod,
  font,
  fstyle,
  fweight,
  width,
  height,
  prepend,
  val,
  append,
  colour,
  decimals,
  size,
  align,
  errorCode,
  errorMessage
) {
  colour = colour || '4444CC'
  size = size || '8'
  font = font || '5'
  fstyle = fstyle || '2'
  fweight = fweight || '1'
  align = align || 'center'

  var fontsize

  if (size === '0') {
    fontsize = 6
  }
  if (size === '1') {
    fontsize = 8
  }
  if (size === '2') {
    fontsize = 10
  }
  if (size === '3') {
    fontsize = 12
  }
  if (size === '4') {
    fontsize = 14
  }
  if (size === '5') {
    fontsize = 16
  }
  if (size === '6') {
    fontsize = 18
  }
  if (size === '7') {
    fontsize = 20
  }
  if (size === '8') {
    fontsize = 22
  }
  if (size === '9') {
    fontsize = 24
  }
  if (size === '10') {
    fontsize = 28
  }
  if (size === '11') {
    fontsize = 32
  }
  if (size === '12') {
    fontsize = 36
  }
  if (size === '13') {
    fontsize = 40
  }
  if (size === '14') {
    fontsize = 18
  } //default value so that not size 40 is always the default

  var fontname

  if (font === '0') {
    fontname = 'Impact'
  }
  if (font === '1') {
    fontname = 'Georgia'
  }
  if (font === '2') {
    fontname = 'Arial'
  }
  if (font === '3') {
    fontname = 'Courier New'
  }
  if (font === '4') {
    fontname = 'Comic Sans MS'
  }
  if (font === '5') {
    fontname = 'Helvetica'
  }
  if (font === '6') {
    fontname = 'Helvetica Neue'
  }
  if (font === '7') {
    fontname = 'sans-serif'
  }
  if (font === '8') {
    fontname = 'Arial Narrow'
  }
  if (font === '9') {
    fontname = 'Arial Black'
  }

  var fontstyle

  if (fstyle === '0') {
    fontstyle = 'oblique'
  }
  if (fstyle === '1') {
    fontstyle = 'italic'
  }
  if (fstyle === '2') {
    fontstyle = 'normal'
  }

  var fontweight

  if (fweight === '0') {
    fontweight = 'normal'
  }
  if (fweight === '1') {
    fontweight = 'bold'
  }

  if (decimals < 0) {
    if (val >= 100) {
      val = val.toFixed(0)
    } else if (val >= 10) {
      val = val.toFixed(1)
    } else if (val <= -100) {
      val = val.toFixed(0)
    } else if (val <= -10) {
      val = val.toFixed(1)
    } else {
      val = val.toFixed(2)
    }
    val = parseFloat(val)
  } else {
    val = val.toFixed(decimals)
  }

  if (colour.indexOf('#') === -1) {
    // Fix missing "#" on colour if needed
    colour = '#' + colour
  }

  kwhperiod.css({
    color: colour,
    font: fontstyle + ' ' + fontweight + ' ' + fontsize + 'px ' + fontname,
    'text-align': align,
    'line-height': height + 'px'
  })

  if (errorCode === '1') {
    kwhperiod.html(errorMessage)
  } else {
    kwhperiod.html(prepend + val + append)
  }
}

function kwhperiod_draw () {
  $('.kwhperiod').each(function (index) {
    var kwhperiod = $(this)
    var errorMessage = $(this).attr('errormessagedisplayed')
    if (errorMessage === '' || errorMessage === undefined) {
      //Error Message parameter is empty
      errorMessage = 'Feed Timeout'
    }
    var errorTimeout = kwhperiod.attr('timeout')
    if (errorTimeout === '' || errorTimeout === undefined) {
      //Timeout parameter is empty
      errorTimeout = 0
    }

    var font = kwhperiod.attr('font')
    var feedid = kwhperiod.attr('feedid')
    if (assocfeed[feedid] != undefined) feedid = assocfeed[feedid] // convert tag:name to feedid
    if (associd[feedid] === undefined) {
      console.log('Review config for feed id of ' + kwhperiod.attr('class'))
      return
    }
    var val = associd[feedid]['value'] * 1

    var period_length = kwhperiod.attr('period_length') * 1
    if (period_length === undefined) period_length = 1 // "day" period default
    if (isNaN(period_length)) period_length = 1

    var period_multiplier = kwhperiod.attr('period_multiplier') * 1
    if (period_multiplier === 0) period_multiplier = 1
    if (period_multiplier === undefined) period_multiplier = 1 // zero and 1 do the same thing, no multiplier by default, time window width equal to period length itself.
    if (isNaN(period_multiplier)) period_multiplier = 1

    var periods_ago = kwhperiod.attr('periods_ago') * 1
    if (periods_ago === undefined) periods_ago = 0 // defaults to the most recent period.
    if (isNaN(periods_ago)) periods_ago = 0

    var period_offset = kwhperiod.attr('period_offset') * 1
    if (period_offset === undefined) period_offset = 0 // default offset is 0
    if (isNaN(period_offset)) period_offset = 0

    var period_quantisation = kwhperiod.attr('period_quantisation') * 1
    if (period_quantisation === undefined) period_quantisation = 1 // default is true
    if (isNaN(period_quantisation)) period_quantisation = 1

    var use_last_year = kwhperiod.attr('use_last_year') * 1
    if (use_last_year === undefined) use_last_year = 0 // default is false
    if (isNaN(use_last_year)) use_last_year = 0

    //------------------------------------------
    // Find period times in milliseconds.
    //------------------------------------------

    var lastperiodStartTime
    var lastperiodEndTime
    var thisperiodStartTime
    var now = new Date()
    now.setMinutes(now.getMinutes() - 5) // clock error compensation
    console.log(now, 'hia-now')

    if (period_length === 0) {
      // hour
      if (period_quantisation) {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setMinutes(0, 0, 0) // reset minutes and seconds of current hour
        console.log(thisperiodStartTime, 'hia-quantised')
      } else {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setHours(thisperiodStartTime.getHours() - 1) // go back one hour exactly
        console.log(thisperiodStartTime, 'hia-not-quantised')
      }

      thisperiodStartTime.setHours(
        thisperiodStartTime.getHours() - (period_multiplier - 1)
      ) // apply multiplier
      console.log(thisperiodStartTime, 'hia-multiplier')

      thisperiodStartTime.setSeconds(
        thisperiodStartTime.getSeconds() + period_offset
      ) // apply offset
      console.log(thisperiodStartTime, 'hia-offset')

      lastperiodStartTime = new Date(thisperiodStartTime)
      lastperiodStartTime.setHours(lastperiodStartTime.getHours() - periods_ago)
      if (use_last_year)
        lastperiodStartTime.setFullYear(lastperiodStartTime.getFullYear() - 1)
      console.log(lastperiodStartTime, 'hia-lastperiodStartTime')

      lastperiodEndTime = new Date(lastperiodStartTime)
      lastperiodEndTime.setHours(
        lastperiodEndTime.getHours() + period_multiplier
      )
      console.log(lastperiodEndTime, 'hia-lastperiodEndTime')
    } else if (period_length === 1) {
      // day
      if (period_quantisation) {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setHours(0, 0, 0, 0)
        console.log(thisperiodStartTime, 'hia-quantised')
      } else {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setDate(thisperiodStartTime.getDate() - 1)
        console.log(thisperiodStartTime, 'hia-not-quantised')
      }

      thisperiodStartTime.setDate(
        thisperiodStartTime.getDate() - (period_multiplier - 1)
      ) // apply multiplier
      console.log(thisperiodStartTime, 'hia-multiplier')
      thisperiodStartTime.setSeconds(
        thisperiodStartTime.getSeconds() + period_offset
      ) // apply offset
      console.log(thisperiodStartTime, 'hia-offset')

      lastperiodStartTime = new Date(thisperiodStartTime)
      lastperiodStartTime.setDate(lastperiodStartTime.getDate() - periods_ago)
      if (use_last_year) {
        lastperiodStartTime.setFullYear(lastperiodStartTime.getFullYear() - 1)
      }
      console.log(lastperiodStartTime, 'hia-lastperiodStartTime')

      lastperiodEndTime = new Date(lastperiodStartTime)
      lastperiodEndTime.setDate(
        lastperiodStartTime.getDate() + period_multiplier
      )
      console.log(lastperiodEndTime, 'hia-lastperiodEndTime')
    } else if (period_length === 2) {
      // week
      if (period_quantisation) {
        thisperiodStartTime = new Date(now)
        // https://stackoverflow.com/a/4156562
        day = now.getDay() || 7 // day is equal to 1 on a Monday, Sunday is converted to a 7.
        if (day !== 1) {
          // only if not Monday
          thisperiodStartTime.setHours((day - 1) * -24) // Set to Monday
        }
        thisperiodStartTime.setHours(0, 0, 0, 0) // Set to midnight
        console.log(thisperiodStartTime, 'hia-quantised')
      } else {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setHours(now.getHours() - 7 * 24)
        console.log(thisperiodStartTime, 'hia-not-quantised')
      }

      thisperiodStartTime.setDate(
        thisperiodStartTime.getDate() - (period_multiplier - 1) * 7
      ) // apply multiplier
      console.log(thisperiodStartTime, 'hia-multiplier')

      thisperiodStartTime.setSeconds(
        thisperiodStartTime.getSeconds() + period_offset
      ) // apply offset
      console.log(thisperiodStartTime, 'hia-offset')

      lastperiodStartTime = new Date(thisperiodStartTime)
      lastperiodStartTime.setDate(
        thisperiodStartTime.getDate() - periods_ago * 7
      )
      if (use_last_year)
        lastperiodStartTime.setFullYear(lastperiodStartTime.getFullYear() - 1)
      console.log(lastperiodStartTime, 'hia-lastperiodStartTime')
      lastperiodEndTime = new Date(lastperiodStartTime)
      lastperiodEndTime.setDate(
        lastperiodStartTime.getDate() + period_multiplier * 7
      )
      console.log(lastperiodEndTime, 'hia-lastperiodEndTime')
    } else if (period_length === 3) {
      // month, calendar month (30 days worth implementing?)
      if (period_quantisation) {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setDate(1) //
        thisperiodStartTime.setHours(0, 0, 0, 0)
        console.log(thisperiodStartTime, 'hia-quantised')
      } else {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setMonth(now.getMonth() - 1)
        console.log(thisperiodStartTime, 'hia-not-quantised')
      }

      thisperiodStartTime.setMonth(
        thisperiodStartTime.getMonth() - (period_multiplier - 1)
      ) // apply multiplier
      console.log(thisperiodStartTime, 'hia-multiplier')

      thisperiodStartTime.setSeconds(
        thisperiodStartTime.getSeconds() + period_offset
      ) // apply offset
      console.log(thisperiodStartTime, 'hia-offset')

      lastperiodStartTime = new Date(thisperiodStartTime)
      lastperiodStartTime.setMonth(thisperiodStartTime.getMonth() - periods_ago)
      if (use_last_year)
        lastperiodStartTime.setFullYear(lastperiodStartTime.getFullYear() - 1)

      console.log(lastperiodStartTime, 'hia-lastperiodStartTime')

      lastperiodEndTime = new Date(lastperiodStartTime)
      lastperiodEndTime.setMonth(
        lastperiodStartTime.getMonth() + period_multiplier
      )
      console.log(lastperiodEndTime, 'hia-lastperiodEndTime')
    } else if (period_length === 4) {
      // year
      if (period_quantisation) {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setMonth(0, 1)
        thisperiodStartTime.setHours(0, 0, 0, 0)
        console.log(thisperiodStartTime, 'hia-quantised')
      } else {
        thisperiodStartTime = new Date(now)
        thisperiodStartTime.setFullYear(now.getFullYear() - 1)
        // console.log(now.getFullYear(),'hia-not-quantised')

        console.log(thisperiodStartTime, 'hia-not-quantised')
      }

      thisperiodStartTime.setFullYear(
        thisperiodStartTime.getFullYear() - (period_multiplier - 1)
      ) // apply multiplier
      console.log(thisperiodStartTime, 'hia-multiplier')

      thisperiodStartTime.setSeconds(
        thisperiodStartTime.getSeconds() + period_offset
      ) // apply offset
      console.log(thisperiodStartTime, 'hia-offset')

      lastperiodStartTime = new Date(thisperiodStartTime)
      lastperiodStartTime.setFullYear(
        thisperiodStartTime.getFullYear() - periods_ago
      )
      if (use_last_year)
        lastperiodStartTime.setFullYear(lastperiodStartTime.getFullYear() - 1)

      console.log(lastperiodStartTime, 'hia-lastperiodStartTime')

      lastperiodEndTime = new Date(lastperiodStartTime)
      lastperiodEndTime.setFullYear(
        lastperiodStartTime.getFullYear() + period_multiplier
      )
      console.log(lastperiodEndTime, 'hia-lastperiodEndTime')
    }

    //------------------------------------------
    // Fetch Values and calculate results.
    //------------------------------------------
    /*
        lastperiod_end_value
        lastperiod_start_value
        thisperiod_start_value
        now
        */

    if (periods_ago > 0 || use_last_year) {
      // lastperiod_end_value
      var result = feed.get_value(feedid, lastperiodEndTime.getTime())
      console.log(result, 'past period end')
      var lastperiod_end_value = result[1]

      // lastperiod_start_value
      var result = feed.get_value(feedid, lastperiodStartTime.getTime())
      console.log(result, 'past period start')
      var lastperiod_start_value = result[1]

      // ... and calculate the result of our time window.
      val = lastperiod_end_value - lastperiod_start_value
    } else {
      // thisperiod_end_value
      //now.setSeconds(0,0)
      var result = feed.get_value(feedid, now.getTime())
      console.log(result, 'recent period end / now')
      var thisperiod_end_value = result[1]

      // thisperiod_start_value
      //thisperiodStartTime.setSeconds(0,0)
      var result = feed.get_value(feedid, thisperiodStartTime.getTime())
      console.log(result, 'recent period start')
      var thisperiod_start_value = result[1]

      // ... and calculate the result of our time window.
      val = thisperiod_end_value - thisperiod_start_value
    }

    if (val === undefined) {
      val = 0
    }
    if (isNaN(val)) {
      val = 0
    }

    var latest_feedtime = associd[feedid]['time'] * 1
    var errorCode = '0'

    var unix_now = Date.now() / 1000
    var errorTime = parseInt(latest_feedtime) + parseInt(errorTimeout)
    //	console.log(unix_now - errorTime);

    if (errorTimeout > 0 && unix_now >= errorTime) {
      errorCode = '1'
    }
    //console.log(errorCode);

    var size = kwhperiod.attr('size')

    var decimals = kwhperiod.attr('decimals')

    if (decimals === undefined) {
      decimals = -1
    }

    // backwards compatibility
    var unitend = kwhperiod.attr('unitend')
    var units = kwhperiod.attr('units')

    // new options
    var prepend = kwhperiod.attr('prepend')
    var append = kwhperiod.attr('append')

    // check if new options are undefined: use old
    if (prepend == undefined && append == undefined) {
      if (unitend != undefined && units != undefined) {
        if (unitend === '0') {
          append = units
          prepend = ''
        } else if (unitend === '1') {
          prepend = units
          append = ''
        }
      } else {
        prepend = ''
        append = ''
      }
    }

    draw_kwhperiod(
      kwhperiod,
      kwhperiod.attr('font'),
      kwhperiod.attr('fstyle'),
      kwhperiod.attr('fweight'),
      kwhperiod.width(),
      kwhperiod.height(),
      prepend,
      val,
      append,
      kwhperiod.attr('colour'),
      kwhperiod.attr('decimals'),
      kwhperiod.attr('size'),
      kwhperiod.attr('align'),
      errorCode,
      errorMessage
    )
  })
}

function kwhperiod_init () {
  $('.kwhperiod').html('')
  kwhperiod_draw()
}
function kwhperiod_slowupdate () {
  kwhperiod_draw()
}
function kwhperiod_fastupdate () {
  //kwhperiod_draw();
}
