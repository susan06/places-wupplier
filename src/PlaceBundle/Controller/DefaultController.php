<?php

namespace PlaceBundle\Controller;

use PlaceBundle\Entity\Location;
use PlaceBundle\Entity\Place;
use PlaceBundle\Form\LocationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    /**
     *  index -show form of search
     *
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(LocationType::class, null,
            array('action' => $this->generateUrl('search'), 'method' => 'POST'));
        $form->handleRequest($request);

        return $this->render('@Place/Default/index.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function searchAction(Request $request)
    {
        $data = $request->request->all();
        if(!isset($data['placebundle_location']['term']) ||
            !isset($data['placebundle_location']['lat']) ||
            !isset($data['placebundle_location']['lng'])
        ) {
            return $this->redirectToRoute('place_homepage');
        }
        $term = $data['placebundle_location']['term'];
        $parameters = [
            'intent' => 'checkin',
            'll' => $data['placebundle_location']['lat'].','.$data['placebundle_location']['lng'],
            'query' => $term,
            'limit' => 25
        ];
        $results = $this->webservice('venues/search', $parameters);
        $response = null;
        foreach ($results as $result) {
            foreach ($result as $item) {
                $response[] = $item;
            }
        }
        $results = $response;
        $status = (isset($results[0]['code'])) ? $results[0]['code'] : 500;
        $places = (isset($results[1]['venues'])) ? $results[1]['venues'] : null;

        return $this->render('@Place/Default/results.html.twig', [
            'status' => $status,
            'places' => $places,
            'term' => $term,
            'position' => 0,
            'location' => [
                'lat' => $data['placebundle_location']['lat'],
                'lng' => $data['placebundle_location']['lng']
            ]
        ]);
    }

    /**
     *  details by venues_id
     *
     * @Route("/details/{id}", name="details")
     */
    public function detailsAction($id, $return = false)
    {
        $results = $this->webservice('venues',['venue_id' => $id]);
        $response = null;
        foreach ($results as $result) {
            foreach ($result as $item) {
                $response[] = $item;
            }
        }
        $results = $response;
        $status = (isset($results[0]['code'])) ? $results[0]['code'] : 500;
        $place = (isset($results[1]['venue'])) ? $results[1]['venue'] : null;

        if($return) {
            return $place;
        }

        return $this->render('@Place/Default/details.html.twig', ['status' => $status, 'place' => $place]);
    }

    /**
     *  get list of my favorites
     *
     * @Route("/favorites", name="favorites")
     */
    public function favoritesAction(Request $request)
    {
        $lat = $id = $request->query->get('lat');
        $lng = $id = $request->query->get('lng');
        $repository = $this->getDoctrine()
            ->getRepository('PlaceBundle:Location');
        $location = $repository->findOneBy(
            array('lat' => $lat, 'lng' => $lng)
        );
        $places = [];
        if($location) {
            $places = $location->getPlaces();
        }
        $list = [];
        foreach ($places as $place) {
            $response = $this->detailsAction($place->getVenuesId(), true);
            if($response) {
                $list[] = $response;
            }
        }

        return $this->render('@Place/Default/favorites.html.twig', ['location' => $location, 'places' => $list]);
    }

    /**
     *  conect to webservice
     *
     */
    private function webservice($request, $parameters = [])
    {
        $client = $this->container->get('jcroll_foursquare_client');
        $command = $client->getCommand($request, $parameters);
        $results = (array) $client->execute($command);

        return $results;
    }

    /**
     *  get photo by venue_id
     *
     */
    public function photosAction(Request $request)
    {
        $id = $request->query->get('id');
        $response = $this->detailsAction($id, true);
        $photo_name = 'images/featured1.jpg';
        if(isset($response['photos']['groups'][0]['items'][0])) {
            $photo = $response['photos']['groups'][0]['items'][0];
            $photo_name = $photo['prefix'].$photo['width'].'x'.$photo['height'].$photo['suffix'];
        }

        return $this->json($photo_name);
    }

    /**
     *  add places to favorites
     *
     *  @Route("/favorites/add", name="add_favorites")
     */
    public function addFavoritesAction(Request $request)
    {
        $id = $request->query->get('id');
        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');
        $response = ['success' => false, 'message' => 'Ocurrió un error, intente de nuevo.'];
        $place = $this->saveFavorite($lat, $lng, $id);
        if($place) {
            $response = ['success' => true, 'message' => 'Se ha agregado el lugar a sus favoritos.'];
        }

        return $this->json($response);
    }

    /**
     *  save location by lat and lng
     *
     */
    public function saveLocation($lat, $lng) 
    {
        $repositoryLocation = $this->getDoctrine()
            ->getRepository('PlaceBundle:Location');
        $location = $repositoryLocation->findOneBy(
            array('lat' => $lat, 'lng' => $lng)
        );

        if(!$location) {
            $entityManager = $this->getDoctrine()->getManager();
            $location = new Location();
            $location->setLat($lat);
            $location->setLng($lng);
            $entityManager->persist($location);
            $entityManager->flush();
        }

        return $location;
    }

    /**
     *  save place by Id and location
     *
     */
    public function saveFavorite($lat, $lng, $venue)
    {
        $location = $this->saveLocation($lat, $lng);

        $repositoryPlace = $this->getDoctrine()
            ->getRepository('PlaceBundle:Place');
        $place = $repositoryPlace->findOneBy(
            array('locationId' => $location->getId(), 'venuesId' => $venue)
        );

        if(!$place) {
            $entityManager = $this->getDoctrine()->getManager();
            $place = new Place();
            $place->setLocation($location);
            $place->setVenuesId($venue);
            $entityManager->persist($place);
            $entityManager->flush();
        }

        return $place->getId();
    }
}
