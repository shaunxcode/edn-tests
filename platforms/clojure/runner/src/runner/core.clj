(ns runner.core
  (:require [clojure.edn :as edn]
            [clojure.string :as string])
  (:import java.io.File))

(defn tagged-item [tag value] {:tag tag :value value})

(defn comp-forms [clj-file edn-file]
  (do (println "LOAD" edn-file)
  (= (read-string (slurp clj-file))
     (edn/read-string (slurp edn-file)))))


(defn get-current-directory []
  (. (File. ".") getCanonicalPath))

(defn -main [] 
  (let [cur-dir (get-current-directory)
        edn-dir (str cur-dir "/../../../valid-edn/")
        clj-dir (str cur-dir "/../")
        edn-files (.listFiles (File. edn-dir))]
       (doseq [file edn-files]
              (let [file-name (first (string/split (. file getName) #"\."))
                    result (comp-forms (str clj-dir file-name ".clj")
                                       (str edn-dir file-name ".edn"))]
                    (println "TEST:" file-name (pr-str result))))))
