<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// if (!function_exists('fetch_table_data')) {

//     function fetch_table_data($moduleId, $CallingUrl, $ListUrl, $pageNo = 0, $limit = 10, $filter = [], $Type) {

//         $CI =& get_instance();
//         $offset = $pageNo ? ($pageNo - 1) * $limit : 0;

//         $resp = $CI->globalservice->getBaseMainPageTablePagination(
//             $moduleId,
//             $CallingUrl,
//             $ListUrl,
//             $pageNo,
//             $limit,
//             $offset,
//             $filter,
//             [],
//             $Type
//         );

//         $visibleColumns = array_filter($resp->AllViewColumns, fn($c) => $c->IsMainPageApplicable == 1);

//         $htmlRows = '';
//         foreach ($resp->DataLists as $list) {
//             $htmlRows .= '<tr>';
//             foreach ($visibleColumns as $col) {
//                 $field = $col->FieldName;
//                 $htmlRows .= '<td>' . htmlspecialchars($list->$field) . '</td>';
//             }
//             $htmlRows .= '</tr>';
//         }

//         return (object)[
//             'Error'      => false,
//             'Columns'    => $visibleColumns,
//             'HtmlRows'   => $htmlRows,
//             'Pagination' => $resp->Pagination
//         ];

//     }
    
// }