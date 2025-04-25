/**
 * FileSales
 * 
 * Commerce addon to sale files
 *
 * @category    plugin
 * @version     1.0.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic
 * @internal    @properties &filetvid=File TV id;text; &paidstatusid=Order paid status id;text;3 &fstemplates=Templates folder;text;assets/plugins/filesales/tpl/ &lifetime=Link lifetime;text;24 &fssubject=Report message subject;text;Ссылки на файлы &driver=Driver class;text;Pathologic\FileSales\Drivers\Readfile
 * @internal    @events OnPageNotFound,OnBeforeOrderHistoryUpdate,OnManagerBeforeOrderRender,OnBeforeCartItemAdding,OnBeforeCartItemUpdating,OnPluginFormSave
 */

return require MODX_BASE_PATH . 'assets/plugins/filesales/plugin.filesales.php';
