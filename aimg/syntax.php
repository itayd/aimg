<?php
/**
 * Annotated Image
 * based on Fotonotes(tm) see http://www.fotonotes.net/
 *
 * Syntax: 
 *  {{aimg>media.png?999x999|Title}} 
 *  @top,left,width,height | note title text
 *  note text line 1
 *  ...
 *  note text line 2
 *  ~author_name
 *  @...
 *  ~
 *  ...
 *  {{<aimg}}
 * 
 * Example:
 * {{aimg> two.jpg?300x300 |Bla}}
 * @10,10,100,100|title text
 * ~zumi
 * @120, 110, 50, 50|title with a [[link]]
 * a centered image: \\
 * {{ two.jpg?100x100 }} \\
 * and a link((footnote)): \\
 * [[this is a very interesting link]]
 * ~
 * {{<aimg}}
 *
 * @license    Released under the Open Source License v2.1 or later (due to Fotonotes)
 * @author     Itay Donenhirsch (itay@bazoo.org)
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
class syntax_plugin_aimg extends DokuWiki_Syntax_Plugin {
 
	function syntax_plugin_aimg() {
		$this->id_seq_num = 0;
	}
	
    function getType(){
        return 'container';
    }
 
    function getAllowedTypes() { 
        return array('formatting','substition','disabled','protected','container','paragraphs');
    }
 
	function getPType() { 
		return 'block';
	}
 
    function getSort(){
        return 311;
    }
  
    function connectTo($mode) {
	  $this->Lexer->addEntryPattern('\{\{aimg>[^\}]+\}\}',$mode,'plugin_aimg');
    }
 
    function postConnect() {
	  $this->Lexer->addPattern('^@.*?\n\~.*?\n','plugin_aimg');
	  $this->Lexer->addExitPattern('\{\{<aimg\}\}','plugin_aimg');
	}
 
	function _imgToId($img,$pos) {
	
		if ($img['title']) {
			$img_id = str_replace(':','',cleanID($img['title']));
			$img_id = ltrim($img_id, '0123456789._-');
		}
		
		if (empty($img_id)) {
			if ($img['type'] == 'internalmedia') {
			  $src = $img['src'];
			  resolve_mediaid(getNS($ID),$src, $exists);
			  $nssep = ($conf['useslash']) ? '[:;/]' : '[:;]';
			  $img_id = preg_replace('!.*'.$nssep.'!','',$src);
			} else {
			  $src = parse_url($img['src']);
			  $img_id = str_replace(':','',cleanID($src['host'].$src['path'].$src['query']));
			  $img_id = ltrim($img_id, '0123456789._-');
			}
			if (empty($img_id)) {
			  $img_id = 'aimg'.$pos;
			}
		}
		
		return $img_id;
	}
	
    function handle($match, $state, $pos, Doku_Handler $handler){
        switch ($state) {
		case DOKU_LEXER_ENTER :
			$img = Doku_Handler_Parse_Media(substr($match, 7, -2));
			$img_id = $this->_imgToId( $img, $pos );
		    $img_args = array( $img_id, $img['type'], $img['src'], $img['title'],
						  $img['align'], $img['width'], $img['height'], 
						  $img['cache']);
			return array( $state, $img_args );

		case DOKU_LEXER_MATCHED:
			return array( $state, $match );
		
		case DOKU_LEXER_EXIT :       
		default:
			return array( $state );
        }
	}
 
	// adapted from image_map plugin
	function _render_image( &$renderer, $img_args ) {
	
		list($img_id, $img_type, $img_src, $img_title,
			 $img_align, $img_width, $img_height, 
			 $img_cache) = $img_args;

			 if ($img_type == 'internalmedia') {
			resolve_mediaid(getNS($ID),$img_src, $exists);
		}

		$img_src = ml($img_src,array('w'=>$img_width,'h'=>$img_height,'cache'=>$img_cache));
		$renderer->doc .= ' <img src="'.$img_src.'" class="media" ';
		if (!is_null($img_title)) {
		  $img_title = $renderer->_xmlEntities($img_title);
		  $renderer->doc .= ' title="'.$img_title.'"';
		  $renderer->doc .= ' alt="'.$img_title.'"';
		} else {
		  $renderer->doc .= ' alt=""';
		}
		if (!is_null($img_width)) {
		  $renderer->doc .= ' width="'.$renderer->_xmlEntities($img_width).'"';
		}
		
		if (!is_null($img_height)) {
			$renderer->doc .= ' height="'.$renderer->_xmlEntities($img_height).'"';
		}
		
		$renderer->doc .= ' />'.DOKU_LF;
		
		return $img_id;
	}
	
    function render($mode, Doku_Renderer $renderer, $data) {
	
        if ( substr($mode,0,5) == 'xhtml' ) {
			$state = $data[0];
			
			switch($state) {
				case DOKU_LEXER_ENTER :
				
					$arg = $data[1];
				
					$this->img_id = $arg[0];
					$this->img_width = $arg[5];
					$this->img_height = $arg[6];
					$img_align = $arg[4];

					$align_style = '';
					switch( $img_align ) {
					case 'left':
						$align_style = 'margin-right: auto;';
						break;
					case 'right':
						$align_style = 'margin-left: auto;';
						break;
					case 'center':
					case 'centre':
						$align_style = 'margin: 0 auto;';
						break;
					}

					$style = 'width: '.$this->img_width.'px; height: '.$this->img_height.'px; '.$align_style;
					
					$renderer->doc .= '<div id="fn-canvas-id-'.$this->img_id.'" class="fn-canvas fn-container-active" style="'.$style.'" >'.DOKU_LF;
					$renderer->doc .= ' <div id="'.$this->img_id.'" class="fn-container fn-container-active" >'.DOKU_LF;

					$this->_render_image( $renderer, $arg );

					break;
					
				case DOKU_LEXER_MATCHED:
					$this->id_seq_num++;
					$match = $data[1];
					
					list($header,$body) = explode( "\n", substr($match, 1, -1), 2 );
					list($coords,$title) = explode( "|", $header, 2 );
					
					list($top,$left,$width,$height) = explode( ",", $coords );
					$style = 'left: '.$left.'px; top: '.$top.'px; width: '.$width.'px; height: '.$height.'px;';
										
					list($content,$author) = preg_split( "/\n~/", $body, 2 );

					// fix for case with signature right after title
					if ( $content[0] == '~' ) {
						$author = substr( $content, 1 );
						$content = '';
					}
					
					$renderer->doc .= '  <div class="fn-area" id="'.$this->img_id.'_'.$this->id_seq_num.'" style="'.$style.'">'.DOKU_LF;
					$renderer->doc .= '   <div class="fn-note">'.DOKU_LF;
					$renderer->doc .= '    <span class="fn-note-title">';					
					$renderer->doc .= p_render($mode, p_get_instructions( $title ), $dummy);
					$renderer->doc .= '</span>'.DOKU_LF;
					$renderer->doc .= '    <span class="fn-note-content">';
					$renderer->doc .= p_render($mode, p_get_instructions( $content ), $dummy);
					$renderer->doc .= '</span>'.DOKU_LF;
					$renderer->doc .= '    <span class="fn-note-author">';
					$renderer->doc .= p_render($mode, p_get_instructions( $author ), $dummy);
					$renderer->doc .= '</span>'.DOKU_LF;
					$renderer->doc .= '   </div>'.DOKU_LF;
					$renderer->doc .= '  </div>'.DOKU_LF;
					break;

				case DOKU_LEXER_EXIT :       
					$renderer->doc .= ' </div>'.DOKU_LF.'</div>'.DOKU_LF;
					break;
			}
			return true;
		} else {
			return false;
		}
    }
}
?>
