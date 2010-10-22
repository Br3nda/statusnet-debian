jQuery(document).ready(function($){
  $('notices_primary').infinitescroll({
    debug: false,
    infiniteScroll  : false,
    nextSelector    : 'body#public li.nav_next a,'+
                      'body#all li.nav_next a,'+
                      'body#showstream li.nav_next a,'+
                      'body#replies li.nav_next a,'+
                      'body#showfavorites li.nav_next a,'+
                      'body#showgroup li.nav_next a,'+
                      'body#favorited li.nav_next a',
    loadingImg      : $('address .url')[0].href+'plugins/InfiniteScroll/ajax-loader.gif',
    text            : "<em>Loading the next set of posts...</em>",
    donetext        : "<em>Congratulations, you\'ve reached the end of the Internet.</em>",
    navSelector     : ".pagination",
    contentSelector : "#notices_primary ol.notices",
    itemSelector    : "#notices_primary ol.notices li"
    },function(){
        SN.Init.Notices();
    });
});
