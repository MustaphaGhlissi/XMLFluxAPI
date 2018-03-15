<?php

namespace App\Controller\API;

use FOS\RestBundle\Decoder\XmlDecoder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class APIController
 * @package App\Controller\API
 * @Route("/api")
 */
class APIController extends Controller
{
    /**
     * @Rest\View()
     * @Rest\Post("/products/flux", name="api_product_flux")
     */
    public function index(Request $request)
    {
        if(!$this->isGranted('ROLE_ADMIN'))
        {
            return new JsonResponse(['code'=>403, 'message'=>'Accès réfusé']);
        }

        if(0 === strpos($request->headers->get('Content-Type'),'application/json'))
        {
            $data = json_decode($request->getContent(),true);
        }
        else
        {
            $data = $request->request->all();
        }


        $json = "http://pf.tradetracker.net/?aid=259193&encoding=utf-8&type=json&fid=633775&categoryType=2&additionalType=2";

        $crawler = new Crawler(file_get_contents($data['url']));
        $products = $crawler->filter('products')->children();
        $parsedProduct = [];
        $parsedProducts = [];
        foreach ($products as $product)
        {
            $childs = $product->childNodes;
            foreach ($childs as $child)
            {
                if($child->nodeName !== '#text')
               {
                   $last = $child->nextSibling;
                   if($last && $last->nodeType !== 1)
                   {
                       foreach ($child->childNodes as $prop)
                       {
                           if($prop->nodeName !== '#text')
                           {
                               $parsedProduct[$prop->nodeName] = $prop->nodeValue;
                           }
                       }
                   }
                   else
                   {
                       $name = $child->nodeName;
                       $parsedProduct[$name] = $child->nodeValue;
                   }
               }
            }
            array_push($parsedProducts,$parsedProduct);
        }

        $data['products'] = $parsedProducts;
        $this->get('jms_serializer')->serialize($data,'json');
        return $data;
    }
}
