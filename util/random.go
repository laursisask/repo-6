package util

import (
	"github.com/google/uuid"
	"math/rand"
	"time"
)

type RandomStringGenerator struct {
	seededRandom *rand.Rand
	buffer       []byte
	Size         int
}

func NewRandomStringGenerator(stringSize int) *RandomStringGenerator {

	return &RandomStringGenerator{
		seededRandom: rand.New(rand.NewSource(time.Now().UnixNano())),
		buffer:       make([]byte, stringSize),
		Size:         stringSize,
	}
}

func (gen *RandomStringGenerator) RandomString() string {
	var random, _ = uuid.NewRandom()
	return random.String()
}
