import { rule } from "postcss";

export const useDebounce = (func, delay) => {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => {
      func(...args);
    }, delay);
  };
};

export const BearToken = (passedToken) => {
  return {
    headers: {
      Authorization: `Bearer ${passedToken}`,
      'Content-Type': 'multipart/form-data', // Ensure this header for file uploads
    },
  };
};

export const formPost = async (routeName,payload) => {
    try {
        let processing = true;

        const response = await axios.post(route(routeName),payload);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: response.message,
                icon: "success",
                background: "#0b0b0b",
                color: "#ffffff",
                confirmButtonColor: "#eab308",
            });
        }

        processing = false;

    } catch (error) {

        Swal.fire({
            title: error.message,
            text: message,
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });

    } finally {
        processing = false;
    }
}

export const formPut = async (routeName,payload) => {
    try {
        let processing = true;

        const response = await axios.put(route(routeName),payload);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: response.message,
                icon: "success",
                background: "#0b0b0b",
                color: "#ffffff",
                confirmButtonColor: "#eab308",
            });
        }

        processing = false;

    } catch (error) {

        Swal.fire({
            title: error.message,
            text: message,
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });

    } finally {
        processing = false;
    }
}

export const formPatch = async (routeName,payload) => {
    try {
        let processing = true;

        const response = await axios.put(route(routeName),payload);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: response.message,
                icon: "success",
                background: "#0b0b0b",
                color: "#ffffff",
                confirmButtonColor: "#eab308",
            });
        }

        processing = false;

    } catch (error) {

        Swal.fire({
            title: error.message,
            text: message,
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });

    } finally {
        processing = false;
    }
}

export const formDelete = async (routeName,payload) => {
    try {
        let processing = true;

        const response = await axios.delete(route(routeName),payload);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: response.message,
                icon: "success",
                background: "#0b0b0b",
                color: "#ffffff",
                confirmButtonColor: "#eab308",
            });
        }

        processing = false;

    } catch (error) {

        Swal.fire({
            title: error.message,
            text: message,
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });

    } finally {
        processing = false;
    }
}

export const formGet = async (routeName,payload) => {
    try {
        let processing = true;

        const response = await axios.get(route(routeName),payload);
        
        return response.data;

        processing = false;

    } catch (error) {

        Swal.fire({
            title: error.message,
            text: message,
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });

    } finally {
        processing = false;
    }
}


prompt:

Create me a universal form handler that can be used in monolith and decoupled architecture

Features
-has debounce request options
-security checking before sending to server
-can do long  pooling
-can do websocket request
-ability to suspend request when suspicious number of request is detected.

Highlight Features
 -can handle any auth request like Oauths, Third party auth api.

options-
-confirmation before request options on some selected request types.


my goal is to lessen the set up of a developer because form request set up  is to time consuming. 
I want the developer to just call a function pass the needed parameter and voala!
That's why i plan to shorten the process of set-up.

I know this request hooks is a good project make it uploadable to NPM and other...


call it LazyHooks and LazyRequests

rule
-follow coding standards and principles.
